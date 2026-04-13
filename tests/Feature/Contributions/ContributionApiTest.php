<?php

namespace Tests\Feature\Contributions;

use App\Modules\Contributions\Domain\Enums\ContributionAction;
use App\Modules\Contributions\Domain\Enums\ContributionStatus;
use App\Modules\Contributions\Domain\Enums\ContributionType;
use App\Modules\Contributions\Domain\Models\Contribution;
use App\Modules\Ingredients\Domain\Models\Ingredient;
use App\Modules\Moderation\Domain\Enums\ModerationActionType;
use App\Modules\Users\Domain\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ContributionApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_submit_a_structured_contribution_that_defaults_to_pending(): void
    {
        $user = User::factory()->create();
        $ingredient = Ingredient::factory()->create([
            'name' => 'Tomato',
            'slug' => 'tomato',
        ]);
        $substitute = Ingredient::factory()->create([
            'name' => 'Roasted Red Pepper',
            'slug' => 'roasted-red-pepper',
        ]);

        Sanctum::actingAs($user);

        $response = $this->withHeader('X-Request-Id', 'contribution-submit-1')
            ->postJson('/api/v1/me/contributions', [
                'type' => ContributionType::SubstitutionTip->value,
                'subject_type' => 'ingredient',
                'subject_id' => $ingredient->id,
                'payload' => [
                    'substitute_ingredient_id' => $substitute->id,
                    'note' => 'Roasted red pepper keeps the sweetness and texture close enough for soup bases.',
                ],
            ]);

        $contribution = Contribution::query()->firstOrFail();

        $response
            ->assertCreated()
            ->assertHeader('X-Request-Id', 'contribution-submit-1')
            ->assertJsonPath('contribution.status', ContributionStatus::Pending->value)
            ->assertJsonPath('contribution.type', ContributionType::SubstitutionTip->value)
            ->assertJsonPath('contribution.action', ContributionAction::Create->value)
            ->assertJsonPath('contribution.subject.id', $ingredient->id)
            ->assertJsonPath('contribution.payload.substitute_ingredient_id', $substitute->id);

        $this->assertDatabaseHas('contributions', [
            'id' => $contribution->id,
            'submitted_by_user_id' => $user->id,
            'subject_type' => Ingredient::class,
            'subject_id' => $ingredient->id,
            'action' => ContributionAction::Create->value,
            'type' => ContributionType::SubstitutionTip->value,
            'status' => ContributionStatus::Pending->value,
        ]);
    }

    public function test_user_can_report_an_approved_contribution_and_it_enters_moderation(): void
    {
        $reporter = User::factory()->create();
        $contribution = $this->createApprovedContribution();

        Sanctum::actingAs($reporter);

        $response = $this->withHeader('X-Request-Id', 'contribution-report-1')
            ->postJson("/api/v1/me/contributions/{$contribution->id}/reports", [
                'reason_code' => 'incorrect',
                'notes' => 'The substitute suggestion is misleading for the current ingredient pairing.',
            ]);

        $response
            ->assertCreated()
            ->assertJsonPath('contribution.status', ContributionStatus::Flagged->value)
            ->assertJsonPath('moderation_case.status', 'open')
            ->assertJsonPath('moderation_case.reason_code', 'incorrect')
            ->assertJsonPath('contribution.moderation.reports_count', 1);

        $this->assertDatabaseHas('moderation_cases', [
            'contribution_id' => $contribution->id,
            'reported_by_user_id' => $reporter->id,
            'status' => 'open',
            'reason_code' => 'incorrect',
        ]);

        $this->assertDatabaseHas('moderation_actions', [
            'contribution_id' => $contribution->id,
            'actor_user_id' => $reporter->id,
            'action' => ModerationActionType::Reported->value,
            'from_status' => ContributionStatus::Approved->value,
            'to_status' => ContributionStatus::Flagged->value,
            'reason_code' => 'incorrect',
            'request_id' => 'contribution-report-1',
        ]);
    }

    public function test_duplicate_open_reports_from_the_same_user_and_reason_are_rejected(): void
    {
        $reporter = User::factory()->create();
        $contribution = $this->createApprovedContribution();

        Sanctum::actingAs($reporter);

        $this->postJson("/api/v1/me/contributions/{$contribution->id}/reports", [
            'reason_code' => 'spam',
            'notes' => 'This reads like duplicate promotional content.',
        ])->assertCreated();

        $this->postJson("/api/v1/me/contributions/{$contribution->id}/reports", [
            'reason_code' => 'spam',
            'notes' => 'Repeated spam report.',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('reason_code');

        $this->assertDatabaseCount('moderation_actions', 1);
    }

    protected function createApprovedContribution(): Contribution
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
            'status' => ContributionStatus::Approved,
            'payload' => [
                'substitute_ingredient_id' => $substitute->id,
                'note' => 'Works in quick sauces when you need a sweeter profile.',
            ],
            'review_notes' => 'Approved for publication.',
            'reviewed_at' => now(),
        ]);
    }
}
