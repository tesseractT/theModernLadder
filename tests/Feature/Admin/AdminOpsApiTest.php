<?php

namespace Tests\Feature\Admin;

use App\Modules\Admin\Application\Services\AdminEventRecorder;
use App\Modules\AI\Application\Contracts\RecipeExplanationProvider;
use App\Modules\AI\Application\Exceptions\RecipeExplanationProviderException;
use App\Modules\Contributions\Domain\Enums\ContributionAction;
use App\Modules\Contributions\Domain\Enums\ContributionStatus;
use App\Modules\Contributions\Domain\Enums\ContributionType;
use App\Modules\Contributions\Domain\Models\Contribution;
use App\Modules\Ingredients\Domain\Models\Ingredient;
use App\Modules\Moderation\Domain\Enums\ModerationActionType;
use App\Modules\Moderation\Domain\Enums\ModerationCaseStatus;
use App\Modules\Moderation\Domain\Models\ModerationAction;
use App\Modules\Moderation\Domain\Models\ModerationCase;
use App\Modules\Pantry\Domain\Models\PantryItem;
use App\Modules\Recipes\Domain\Models\RecipeTemplate;
use App\Modules\Users\Domain\Enums\UserRole;
use App\Modules\Users\Domain\Models\User;
use Database\Seeders\StarterRecipeTemplateCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Mockery\MockInterface;
use Tests\TestCase;

class AdminOpsApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_only_internal_ops_endpoints_reject_users_and_moderators(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::User,
        ]);
        $moderator = User::factory()->create([
            'role' => UserRole::Moderator,
        ]);

        $endpoints = [
            '/api/v1/admin/moderation/flagged-contributions',
            '/api/v1/admin/moderation/actions',
            '/api/v1/admin/ops/suspicious-activity',
            '/api/v1/admin/ai/failures',
            '/api/v1/admin/audit-events',
        ];

        foreach ([$user, $moderator] as $actor) {
            Sanctum::actingAs($actor);

            foreach ($endpoints as $endpoint) {
                $this->getJson($endpoint)
                    ->assertForbidden()
                    ->assertJsonPath('code', 'forbidden');
            }
        }
    }

    public function test_admin_can_list_flagged_contributions_with_moderation_context(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
        ]);
        $flaggedContribution = $this->createFlaggedContribution(reasonCode: 'incorrect');
        $this->createFlaggedContribution(reasonCode: 'spam');

        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/v1/admin/moderation/flagged-contributions?reason_code=incorrect');

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $flaggedContribution->id)
            ->assertJsonPath('data.0.status', ContributionStatus::Flagged->value)
            ->assertJsonPath('data.0.flagged_context.active_case.reason_code', 'incorrect')
            ->assertJsonPath('data.0.flagged_context.latest_action.action', ModerationActionType::Reported->value)
            ->assertJsonPath('data.0.reports_count', 1);
    }

    public function test_admin_can_list_recent_moderation_actions(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
        ]);
        $moderator = User::factory()->create([
            'role' => UserRole::Moderator,
        ]);
        $contribution = $this->createContributionWithStatus(ContributionStatus::Pending);

        Sanctum::actingAs($moderator);

        $this->withHeader('X-Request-Id', 'admin-actions-moderation-1')
            ->postJson("/api/v1/moderation/contributions/{$contribution->id}/actions", [
                'action' => ModerationActionType::Approved->value,
                'notes' => 'Approved after review for internal tooling verification.',
            ])
            ->assertOk();

        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/v1/admin/moderation/actions?action=approved');

        $response
            ->assertOk()
            ->assertJsonPath('data.0.action', ModerationActionType::Approved->value)
            ->assertJsonPath('data.0.request_id', 'admin-actions-moderation-1')
            ->assertJsonPath('data.0.actor.id', $moderator->id)
            ->assertJsonPath('data.0.target.id', $contribution->id);
    }

    public function test_admin_can_view_suspicious_activity_summary_for_supported_signals(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
        ]);
        $reporter = User::factory()->create();
        $template = RecipeTemplate::factory()->create([
            'title' => 'Ops Failure Template',
            'slug' => 'ops-failure-template',
        ]);

        foreach (range(1, 3) as $index) {
            $contribution = $this->createContributionWithStatus(ContributionStatus::Flagged);
            $moderationCase = ModerationCase::query()->create([
                'subject_type' => $contribution->subject_type,
                'subject_id' => $contribution->subject_id,
                'contribution_id' => $contribution->id,
                'reported_by_user_id' => $reporter->id,
                'status' => ModerationCaseStatus::Open,
                'reason_code' => 'incorrect',
            ]);

            ModerationAction::query()->create([
                'contribution_id' => $contribution->id,
                'moderation_case_id' => $moderationCase->id,
                'actor_user_id' => $reporter->id,
                'action' => ModerationActionType::Reported,
                'from_status' => ContributionStatus::Approved,
                'to_status' => ContributionStatus::Flagged,
                'reason_code' => 'incorrect',
                'request_id' => 'summary-report-'.$index,
            ]);
        }

        $churnedContribution = $this->createContributionWithStatus(ContributionStatus::Approved);
        $moderator = User::factory()->create([
            'role' => UserRole::Moderator,
        ]);

        ModerationAction::query()->create([
            'contribution_id' => $churnedContribution->id,
            'actor_user_id' => $moderator->id,
            'action' => ModerationActionType::Flagged,
            'from_status' => ContributionStatus::Approved,
            'to_status' => ContributionStatus::Flagged,
            'notes' => 'Escalated for further review.',
            'request_id' => 'summary-churn-flagged',
        ]);

        ModerationAction::query()->create([
            'contribution_id' => $churnedContribution->id,
            'actor_user_id' => $moderator->id,
            'action' => ModerationActionType::Approved,
            'from_status' => ContributionStatus::Flagged,
            'to_status' => ContributionStatus::Approved,
            'notes' => 'Re-approved after second pass.',
            'request_id' => 'summary-churn-approved',
        ]);

        $eventRecorder = app(AdminEventRecorder::class);
        $eventRecorder->recordAiExplanationFailure(
            event: 'recipe_template.explanation.failed',
            actorUserId: $reporter->id,
            targetId: $template->id,
            requestId: 'summary-ai-failure-1',
            routeName: 'recipes.templates.explanation.store',
            metadata: [
                'provider' => 'openai',
                'failure_type' => 'RecipeExplanationProviderException',
                'failure_reason' => 'connection_exception',
                'fallback_used' => true,
            ],
        );
        $eventRecorder->recordAiExplanationFailure(
            event: 'recipe_template.explanation.failed',
            actorUserId: $reporter->id,
            targetId: $template->id,
            requestId: 'summary-ai-failure-2',
            routeName: 'recipes.templates.explanation.store',
            metadata: [
                'provider' => 'openai',
                'failure_type' => 'RecipeExplanationProviderException',
                'failure_reason' => 'connection_exception',
                'fallback_used' => false,
            ],
        );

        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/v1/admin/ops/suspicious-activity');

        $response
            ->assertOk()
            ->assertJsonPath('signals.high_report_volume_users.0.user.id', $reporter->id)
            ->assertJsonPath('signals.high_report_volume_users.0.reports_count', 3)
            ->assertJsonPath('signals.contribution_churn.0.contribution.id', $churnedContribution->id)
            ->assertJsonPath('signals.contribution_churn.0.action_count', 2)
            ->assertJsonPath('signals.repeated_ai_failures.0.template.id', $template->id)
            ->assertJsonPath('signals.repeated_ai_failures.0.failures_count', 2);
    }

    public function test_admin_can_list_ai_failure_events_without_secret_leakage(): void
    {
        $this->seed(StarterRecipeTemplateCatalogSeeder::class);

        $admin = User::factory()->create([
            'role' => UserRole::Admin,
        ]);
        $user = User::factory()->create();
        $template = RecipeTemplate::query()->where('slug', 'pineapple-smoothie')->firstOrFail();

        $this->pantryItemForTemplateIngredient($user, $template, 'pineapple');
        $this->pantryItemForTemplateIngredient($user, $template, 'yogurt');

        $this->mock(RecipeExplanationProvider::class, function (MockInterface $mock): void {
            $mock->shouldReceive('generate')
                ->once()
                ->andThrow(new RecipeExplanationProviderException(
                    'Provider failure.',
                    [
                        'api_key' => 'sk-secret',
                        'authorization' => 'Bearer secret-token',
                    ]
                ));
        });

        Sanctum::actingAs($user);

        $this->withHeader('X-Request-Id', 'admin-ai-failure-1')
            ->postJson("/api/v1/recipes/templates/{$template->id}/explanation")
            ->assertOk()
            ->assertJsonPath('source', 'fallback');

        Sanctum::actingAs($admin);

        $response = $this->getJson("/api/v1/admin/ai/failures?template_id={$template->id}");

        $response
            ->assertOk()
            ->assertJsonPath('data.0.event', 'recipe_template.explanation.failed')
            ->assertJsonPath('data.0.request_id', 'admin-ai-failure-1')
            ->assertJsonPath('data.0.target.id', $template->id)
            ->assertJsonPath('data.0.actor.id', $user->id)
            ->assertJsonPath('data.0.fallback_used', true)
            ->assertJsonPath('data.0.failure_type', 'RecipeExplanationProviderException');

        $payload = json_encode($response->json(), JSON_THROW_ON_ERROR);

        $this->assertStringNotContainsString('sk-secret', $payload);
        $this->assertStringNotContainsString('secret-token', $payload);
        $this->assertStringNotContainsString('Bearer', $payload);
    }

    public function test_admin_can_list_queryable_audit_events_without_exposing_tokens(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
        ]);

        $registerResponse = $this->withHeader('X-Request-Id', 'admin-audit-register-1')
            ->postJson('/api/v1/auth/register', [
                'name' => 'Avery Ops',
                'email' => 'avery-ops@example.com',
                'password' => 'Password123!',
                'password_confirmation' => 'Password123!',
                'device_name' => 'ops-device',
            ])
            ->assertCreated();

        $plainTextToken = (string) $registerResponse->json('token');

        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/v1/admin/audit-events?event=auth.register.succeeded');

        $response
            ->assertOk()
            ->assertJsonPath('data.0.event', 'auth.register.succeeded')
            ->assertJsonPath('data.0.request_id', 'admin-audit-register-1')
            ->assertJsonPath('data.0.target_type', 'personal_access_token');

        $payload = json_encode($response->json(), JSON_THROW_ON_ERROR);

        $this->assertStringNotContainsString($plainTextToken, $payload);
        $this->assertStringNotContainsString('Password123!', $payload);
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
                'note' => 'A practical substitution for internal ops testing.',
            ],
        ]);
    }

    protected function createFlaggedContribution(string $reasonCode): Contribution
    {
        $contribution = $this->createContributionWithStatus(ContributionStatus::Flagged);
        $reporter = User::factory()->create();

        $moderationCase = ModerationCase::query()->create([
            'subject_type' => $contribution->subject_type,
            'subject_id' => $contribution->subject_id,
            'contribution_id' => $contribution->id,
            'reported_by_user_id' => $reporter->id,
            'status' => ModerationCaseStatus::Open,
            'reason_code' => $reasonCode,
            'notes' => 'Needs an operator review.',
        ]);

        ModerationAction::query()->create([
            'contribution_id' => $contribution->id,
            'moderation_case_id' => $moderationCase->id,
            'actor_user_id' => $reporter->id,
            'action' => ModerationActionType::Reported,
            'from_status' => ContributionStatus::Approved,
            'to_status' => ContributionStatus::Flagged,
            'reason_code' => $reasonCode,
            'notes' => 'Needs an operator review.',
            'request_id' => 'flagged-list-seed-'.$reasonCode,
        ]);

        return $contribution;
    }

    protected function pantryItemForTemplateIngredient(
        User $user,
        RecipeTemplate $template,
        string $ingredientSlug,
    ): void {
        $ingredientId = $template->templateIngredients()
            ->whereHas('ingredient', fn ($query) => $query->where('slug', $ingredientSlug))
            ->value('ingredient_id');

        PantryItem::query()->create([
            'user_id' => $user->id,
            'ingredient_id' => $ingredientId,
            'entered_name' => 'Seeded Ingredient',
        ]);
    }
}
