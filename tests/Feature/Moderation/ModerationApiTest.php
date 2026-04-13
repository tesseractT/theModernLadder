<?php

namespace Tests\Feature\Moderation;

use App\Modules\Contributions\Domain\Enums\ContributionAction;
use App\Modules\Contributions\Domain\Enums\ContributionStatus;
use App\Modules\Contributions\Domain\Enums\ContributionType;
use App\Modules\Contributions\Domain\Models\Contribution;
use App\Modules\Ingredients\Domain\Models\Ingredient;
use App\Modules\Moderation\Domain\Enums\ModerationActionType;
use App\Modules\Moderation\Domain\Enums\ModerationCaseStatus;
use App\Modules\Moderation\Domain\Models\ModerationAction;
use App\Modules\Moderation\Domain\Models\ModerationCase;
use App\Modules\Users\Domain\Enums\UserRole;
use App\Modules\Users\Domain\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ModerationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_moderator_and_admin_can_access_the_moderation_queue(): void
    {
        $moderator = User::factory()->create([
            'role' => UserRole::Moderator,
        ]);
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
        ]);

        $pending = $this->createContributionWithStatus(ContributionStatus::Pending);
        $flagged = $this->createFlaggedContribution();
        $approved = $this->createContributionWithStatus(ContributionStatus::Approved);

        Sanctum::actingAs($moderator);

        $moderatorResponse = $this->getJson('/api/v1/moderation/contributions');
        $moderatorIds = collect($moderatorResponse->json('data'))->pluck('id')->all();

        $moderatorResponse->assertOk();
        $this->assertContains($pending->id, $moderatorIds);
        $this->assertContains($flagged->id, $moderatorIds);
        $this->assertNotContains($approved->id, $moderatorIds);

        Sanctum::actingAs($admin);

        $this->getJson('/api/v1/moderation/contributions?status=flagged')
            ->assertOk()
            ->assertJsonFragment([
                'id' => $flagged->id,
                'status' => ContributionStatus::Flagged->value,
            ]);
    }

    public function test_regular_users_cannot_access_moderation_queue_or_actions(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::User,
        ]);
        $contribution = $this->createContributionWithStatus(ContributionStatus::Pending);

        Sanctum::actingAs($user);

        $this->getJson('/api/v1/moderation/contributions')
            ->assertForbidden()
            ->assertJsonPath('code', 'forbidden');

        $this->postJson("/api/v1/moderation/contributions/{$contribution->id}/actions", [
            'action' => ModerationActionType::Approved->value,
            'notes' => 'Looks good.',
        ])
            ->assertForbidden()
            ->assertJsonPath('code', 'forbidden');
    }

    public function test_moderator_can_approve_a_pending_contribution_with_notes_and_security_audit_logging(): void
    {
        $moderator = User::factory()->create([
            'role' => UserRole::Moderator,
        ]);
        $contribution = $this->createContributionWithStatus(ContributionStatus::Pending);
        $notes = 'Approved after checking the ingredient relationship and wording.';

        Log::spy();
        Sanctum::actingAs($moderator);

        $response = $this->withHeader('X-Request-Id', 'moderation-approve-1')
            ->postJson("/api/v1/moderation/contributions/{$contribution->id}/actions", [
                'action' => ModerationActionType::Approved->value,
                'notes' => $notes,
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('contribution.status', ContributionStatus::Approved->value)
            ->assertJsonPath('contribution.moderation.latest_note', $notes);

        $this->assertDatabaseHas('contributions', [
            'id' => $contribution->id,
            'status' => ContributionStatus::Approved->value,
            'reviewed_by_user_id' => $moderator->id,
            'review_notes' => $notes,
        ]);

        $this->assertDatabaseHas('moderation_actions', [
            'contribution_id' => $contribution->id,
            'actor_user_id' => $moderator->id,
            'action' => ModerationActionType::Approved->value,
            'from_status' => ContributionStatus::Pending->value,
            'to_status' => ContributionStatus::Approved->value,
            'request_id' => 'moderation-approve-1',
        ]);

        Log::shouldHaveReceived('info')
            ->withArgs(function (string $message, array $context) use ($moderator, $contribution, $notes): bool {
                return $message === 'security.audit'
                    && ($context['event'] ?? null) === 'moderation.contribution.approved'
                    && ($context['actor_id'] ?? null) === (string) $moderator->id
                    && ($context['request_id'] ?? null) === 'moderation-approve-1'
                    && ($context['target_type'] ?? null) === 'contribution'
                    && ($context['target_id'] ?? null) === (string) $contribution->id
                    && ($context['from_status'] ?? null) === ContributionStatus::Pending->value
                    && ($context['to_status'] ?? null) === ContributionStatus::Approved->value
                    && ($context['note_present'] ?? null) === true
                    && ! str_contains(json_encode($context, JSON_THROW_ON_ERROR), $notes);
            })
            ->once();
    }

    public function test_flagged_contribution_can_be_approved_and_active_cases_are_resolved(): void
    {
        $moderator = User::factory()->create([
            'role' => UserRole::Moderator,
        ]);
        $contribution = $this->createFlaggedContribution();

        Sanctum::actingAs($moderator);

        $this->postJson("/api/v1/moderation/contributions/{$contribution->id}/actions", [
            'action' => ModerationActionType::Approved->value,
            'notes' => 'Reports reviewed and the contribution is acceptable as written.',
        ])
            ->assertOk()
            ->assertJsonPath('contribution.status', ContributionStatus::Approved->value);

        $this->assertDatabaseHas('moderation_cases', [
            'contribution_id' => $contribution->id,
            'status' => ModerationCaseStatus::Resolved->value,
            'assigned_to_user_id' => $moderator->id,
        ]);
    }

    public function test_invalid_moderation_transitions_fail_safely(): void
    {
        $moderator = User::factory()->create([
            'role' => UserRole::Moderator,
        ]);
        $contribution = $this->createContributionWithStatus(ContributionStatus::Pending);

        Sanctum::actingAs($moderator);

        $this->postJson("/api/v1/moderation/contributions/{$contribution->id}/actions", [
            'action' => ModerationActionType::Flagged->value,
            'notes' => 'Attempting an invalid direct flag from pending.',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('action');

        $this->assertDatabaseHas('contributions', [
            'id' => $contribution->id,
            'status' => ContributionStatus::Pending->value,
        ]);

        $this->assertDatabaseCount('moderation_actions', 0);
    }

    public function test_moderation_detail_endpoint_returns_contribution_case_and_history_context(): void
    {
        $moderator = User::factory()->create([
            'role' => UserRole::Moderator,
        ]);
        $contribution = $this->createFlaggedContribution();

        Sanctum::actingAs($moderator);

        $response = $this->getJson("/api/v1/moderation/contributions/{$contribution->id}");

        $response
            ->assertOk()
            ->assertJsonPath('contribution.id', $contribution->id)
            ->assertJsonPath('moderation_cases.0.reason_code', 'incorrect');

        $this->assertSame($contribution->id, $response->json('contribution.id'));
        $this->assertSame('reported', $response->json('moderation_history.0.action'));
        $this->assertSame('open', $response->json('moderation_cases.0.status'));
    }

    protected function createContributionWithStatus(ContributionStatus $status): Contribution
    {
        $submitter = User::factory()->create();
        $ingredient = Ingredient::factory()->create();
        $substitute = Ingredient::factory()->create();

        return Contribution::query()->create([
            'submitted_by_user_id' => $submitter->id,
            'subject_type' => Ingredient::class,
            'subject_id' => $ingredient->id,
            'action' => ContributionAction::Create,
            'type' => ContributionType::SubstitutionTip,
            'status' => $status,
            'payload' => [
                'substitute_ingredient_id' => $substitute->id,
                'note' => 'A practical substitution for weeknight cooking.',
            ],
        ]);
    }

    protected function createFlaggedContribution(): Contribution
    {
        $contribution = $this->createContributionWithStatus(ContributionStatus::Flagged);
        $reporter = User::factory()->create();

        $moderationCase = ModerationCase::query()->create([
            'subject_type' => $contribution->subject_type,
            'subject_id' => $contribution->subject_id,
            'contribution_id' => $contribution->id,
            'reported_by_user_id' => $reporter->id,
            'status' => ModerationCaseStatus::Open,
            'reason_code' => 'incorrect',
            'notes' => 'Needs a moderator check.',
        ]);

        ModerationAction::query()->create([
            'contribution_id' => $contribution->id,
            'moderation_case_id' => $moderationCase->id,
            'actor_user_id' => $reporter->id,
            'action' => ModerationActionType::Reported,
            'from_status' => ContributionStatus::Approved,
            'to_status' => ContributionStatus::Flagged,
            'reason_code' => 'incorrect',
            'notes' => 'Needs a moderator check.',
            'request_id' => 'seed-flagged-contribution',
        ]);

        return $contribution;
    }
}
