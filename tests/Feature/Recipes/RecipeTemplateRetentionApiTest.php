<?php

namespace Tests\Feature\Recipes;

use App\Modules\Recipes\Domain\Enums\RecipePlanHorizon;
use App\Modules\Recipes\Domain\Enums\RecipeTemplateInteractionSource;
use App\Modules\Recipes\Domain\Enums\RecipeTemplateInteractionType;
use App\Modules\Recipes\Domain\Models\RecipePlanItem;
use App\Modules\Recipes\Domain\Models\RecipeTemplate;
use App\Modules\Recipes\Domain\Models\RecipeTemplateInteraction;
use App\Modules\Users\Domain\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RecipeTemplateRetentionApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_favorite_flow_is_user_scoped_and_idempotent(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        Sanctum::actingAs($user);

        $template = RecipeTemplate::factory()->create();
        $otherTemplate = RecipeTemplate::factory()->create();

        RecipeTemplateInteraction::query()->create([
            'user_id' => $otherUser->id,
            'recipe_template_id' => $otherTemplate->id,
            'interaction_type' => RecipeTemplateInteractionType::Favorite->value,
            'interacted_at' => now()->subMinute(),
        ]);

        Sanctum::actingAs($user);

        $this->putJson("/api/v1/me/recipe-templates/{$template->id}/favorite")
            ->assertOk()
            ->assertJsonPath('interaction_type', 'favorite')
            ->assertJsonPath('template.id', $template->id);

        $this->putJson("/api/v1/me/recipe-templates/{$template->id}/favorite")
            ->assertOk()
            ->assertJsonPath('interaction_type', 'favorite');

        $this->assertSame(1, RecipeTemplateInteraction::query()
            ->where('user_id', $user->id)
            ->where('recipe_template_id', $template->id)
            ->where('interaction_type', RecipeTemplateInteractionType::Favorite->value)
            ->count());

        $this->getJson('/api/v1/me/recipe-templates/favorites')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.template.id', $template->id)
            ->assertJsonMissing(['id' => $otherTemplate->id]);

        $this->deleteJson("/api/v1/me/recipe-templates/{$template->id}/favorite")
            ->assertOk()
            ->assertJsonPath('message', 'Recipe template removed from favorites.');

        $this->deleteJson("/api/v1/me/recipe-templates/{$template->id}/favorite")
            ->assertOk();

        $this->assertSame(0, RecipeTemplateInteraction::query()
            ->where('user_id', $user->id)
            ->where('recipe_template_id', $template->id)
            ->where('interaction_type', RecipeTemplateInteractionType::Favorite->value)
            ->count());
    }

    public function test_saved_suggestions_use_canonical_template_references_and_handle_duplicates_safely(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $template = RecipeTemplate::factory()->create();
        $otherTemplate = RecipeTemplate::factory()->create();

        RecipeTemplateInteraction::query()->create([
            'user_id' => $otherUser->id,
            'recipe_template_id' => $otherTemplate->id,
            'interaction_type' => RecipeTemplateInteractionType::SavedSuggestion->value,
            'source' => RecipeTemplateInteractionSource::Suggestions->value,
            'goal' => 'drink',
            'interacted_at' => now()->subMinute(),
        ]);

        Sanctum::actingAs($user);

        $this->putJson("/api/v1/me/recipe-templates/{$template->id}/saved-suggestion", [
            'source' => 'suggestions',
            'goal' => 'drink',
        ])
            ->assertOk()
            ->assertJsonPath('interaction_type', 'saved_suggestion')
            ->assertJsonPath('source', 'suggestions')
            ->assertJsonPath('goal', 'drink')
            ->assertJsonPath('template.id', $template->id);

        $this->putJson("/api/v1/me/recipe-templates/{$template->id}/saved-suggestion", [
            'source' => 'recipe_detail',
            'goal' => 'breakfast',
        ])
            ->assertOk()
            ->assertJsonPath('source', 'recipe_detail')
            ->assertJsonPath('goal', 'breakfast');

        $this->assertSame(1, RecipeTemplateInteraction::query()
            ->where('user_id', $user->id)
            ->where('recipe_template_id', $template->id)
            ->where('interaction_type', RecipeTemplateInteractionType::SavedSuggestion->value)
            ->count());

        $this->getJson('/api/v1/me/recipe-templates/saved-suggestions')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.template.id', $template->id)
            ->assertJsonPath('data.0.source', 'recipe_detail')
            ->assertJsonPath('data.0.goal', 'breakfast')
            ->assertJsonMissing(['id' => $otherTemplate->id]);
    }

    public function test_recent_history_is_recorded_deduped_bounded_and_user_scoped(): void
    {
        config()->set('recipes.retention.recent_history.max_entries', 2);

        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $templateOne = RecipeTemplate::factory()->create();
        $templateTwo = RecipeTemplate::factory()->create();
        $templateThree = RecipeTemplate::factory()->create();
        $otherTemplate = RecipeTemplate::factory()->create();

        RecipeTemplateInteraction::query()->create([
            'user_id' => $otherUser->id,
            'recipe_template_id' => $otherTemplate->id,
            'interaction_type' => RecipeTemplateInteractionType::RecentHistory->value,
            'source' => RecipeTemplateInteractionSource::RecipeDetail->value,
            'interacted_at' => now()->subMinute(),
        ]);

        Sanctum::actingAs($user);

        Carbon::setTestNow('2026-04-13 10:00:00');
        $this->getJson("/api/v1/recipes/templates/{$templateOne->id}")->assertOk();
        Carbon::setTestNow('2026-04-13 10:01:00');
        $this->getJson("/api/v1/recipes/templates/{$templateTwo->id}")->assertOk();
        Carbon::setTestNow('2026-04-13 10:02:00');
        $this->getJson("/api/v1/recipes/templates/{$templateOne->id}")->assertOk();
        Carbon::setTestNow('2026-04-13 10:03:00');
        $this->getJson("/api/v1/recipes/templates/{$templateThree->id}")->assertOk();
        Carbon::setTestNow();

        $this->getJson('/api/v1/me/recipe-templates/history')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.template.id', $templateThree->id)
            ->assertJsonPath('data.0.source', 'recipe_detail')
            ->assertJsonPath('data.1.template.id', $templateOne->id)
            ->assertJsonMissing(['id' => $otherTemplate->id]);

        $this->assertSame(2, RecipeTemplateInteraction::query()
            ->where('user_id', $user->id)
            ->where('interaction_type', RecipeTemplateInteractionType::RecentHistory->value)
            ->count());

        $this->assertDatabaseMissing('recipe_template_interactions', [
            'user_id' => $user->id,
            'recipe_template_id' => $templateTwo->id,
            'interaction_type' => RecipeTemplateInteractionType::RecentHistory->value,
        ]);
    }

    public function test_plan_items_can_be_created_listed_updated_deleted_and_scoped_to_the_owner(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        Sanctum::actingAs($user);

        $template = RecipeTemplate::factory()->create();
        $otherTemplate = RecipeTemplate::factory()->create();

        $created = $this->postJson('/api/v1/me/recipe-plans', [
            'recipe_template_id' => $template->id,
            'horizon' => 'today',
            'note' => 'Use the ripe bananas first.',
        ]);

        $created->assertCreated()
            ->assertJsonPath('template.id', $template->id)
            ->assertJsonPath('horizon', 'today')
            ->assertJsonPath('note', 'Use the ripe bananas first.');

        $planItemId = $created->json('id');

        $this->postJson('/api/v1/me/recipe-plans', [
            'recipe_template_id' => $template->id,
            'horizon' => 'today',
            'note' => 'Updated note',
        ])
            ->assertOk()
            ->assertJsonPath('id', $planItemId)
            ->assertJsonPath('note', 'Updated note');

        RecipePlanItem::query()->create([
            'user_id' => $otherUser->id,
            'recipe_template_id' => $otherTemplate->id,
            'horizon' => RecipePlanHorizon::Tomorrow->value,
            'note' => 'Private plan item',
        ]);

        $this->getJson('/api/v1/me/recipe-plans')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $planItemId)
            ->assertJsonPath('data.0.template.id', $template->id)
            ->assertJsonMissing(['id' => $otherTemplate->id]);

        $this->patchJson("/api/v1/me/recipe-plans/{$planItemId}", [
            'horizon' => 'tomorrow',
            'note' => null,
        ])
            ->assertOk()
            ->assertJsonPath('horizon', 'tomorrow')
            ->assertJsonPath('note', null);

        $otherUsersPlan = RecipePlanItem::query()->where('user_id', $otherUser->id)->firstOrFail();

        $this->patchJson("/api/v1/me/recipe-plans/{$otherUsersPlan->id}", [
            'note' => 'Should not work',
        ])->assertNotFound();

        $this->deleteJson("/api/v1/me/recipe-plans/{$otherUsersPlan->id}")
            ->assertNotFound();

        $this->deleteJson("/api/v1/me/recipe-plans/{$planItemId}")
            ->assertOk()
            ->assertJsonPath('message', 'Plan item removed successfully.');

        $this->assertDatabaseMissing('recipe_plan_items', [
            'id' => $planItemId,
        ]);
    }

    public function test_unauthenticated_retention_endpoints_are_rejected(): void
    {
        $template = RecipeTemplate::factory()->create();
        $planItem = RecipePlanItem::query()->create([
            'user_id' => User::factory()->create()->id,
            'recipe_template_id' => $template->id,
            'horizon' => RecipePlanHorizon::Today->value,
        ]);

        $this->getJson('/api/v1/me/recipe-templates/favorites')->assertUnauthorized();
        $this->putJson("/api/v1/me/recipe-templates/{$template->id}/favorite")->assertUnauthorized();
        $this->putJson("/api/v1/me/recipe-templates/{$template->id}/saved-suggestion", [
            'source' => 'suggestions',
        ])->assertUnauthorized();
        $this->getJson('/api/v1/me/recipe-templates/history')->assertUnauthorized();
        $this->getJson('/api/v1/me/recipe-plans')->assertUnauthorized();
        $this->postJson('/api/v1/me/recipe-plans', [
            'recipe_template_id' => $template->id,
            'horizon' => 'today',
        ])->assertUnauthorized();
        $this->patchJson("/api/v1/me/recipe-plans/{$planItem->id}", [
            'note' => 'Nope',
        ])->assertUnauthorized();
        $this->deleteJson("/api/v1/me/recipe-plans/{$planItem->id}")->assertUnauthorized();
    }
}
