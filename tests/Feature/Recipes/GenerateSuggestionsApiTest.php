<?php

namespace Tests\Feature\Recipes;

use App\Modules\AI\Application\Contracts\RecipeExplanationProvider;
use App\Modules\Ingredients\Domain\Models\Ingredient;
use App\Modules\Ingredients\Domain\Models\IngredientAlias;
use App\Modules\Ingredients\Domain\Models\Pairing;
use App\Modules\Ingredients\Domain\Models\Substitution;
use App\Modules\Pantry\Domain\Enums\PantryItemStatus;
use App\Modules\Pantry\Domain\Models\PantryItem;
use App\Modules\Recipes\Domain\Models\RecipeTemplate;
use App\Modules\Recipes\Domain\Models\RecipeTemplateIngredient;
use App\Modules\Shared\Domain\Enums\ContentStatus;
use App\Modules\Users\Domain\Models\User;
use App\Modules\Users\Domain\Models\UserPreference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Mockery\MockInterface;
use Tests\TestCase;

class GenerateSuggestionsApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_suggestion_access_is_rejected(): void
    {
        $this->postJson('/api/v1/me/suggestions')->assertUnauthorized();
    }

    public function test_user_with_empty_pantry_gets_a_clean_empty_result(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $this->postJson('/api/v1/me/suggestions')
            ->assertOk()
            ->assertJsonPath('pantry.count', 0)
            ->assertJsonPath('meta.count', 0)
            ->assertJsonPath('message', 'Add pantry ingredients to get suggestion candidates.')
            ->assertJsonCount(0, 'candidates');
    }

    public function test_pantry_based_suggestions_are_generated_from_active_pantry_items(): void
    {
        $user = User::factory()->create();
        $pineapple = $this->ingredient('Pineapple');
        $banana = $this->ingredient('Banana');
        $yogurt = $this->ingredient('Yogurt');
        $onion = $this->ingredient('Onion');
        $lime = $this->ingredient('Lime');

        $this->recipeTemplate(
            'Tropical Smoothie',
            'drink',
            ['vegetarian'],
            [$pineapple, $banana, $yogurt]
        );
        $this->recipeTemplate(
            'Pineapple Salsa',
            'snack',
            ['vegan', 'vegetarian'],
            [$pineapple, $onion, $lime]
        );

        $this->pantryItem($user, $pineapple);
        $this->pantryItem($user, $banana);
        $this->pantryItem($user, $yogurt);
        $this->pantryItem($user, $onion, status: PantryItemStatus::Archived);

        Sanctum::actingAs($user);

        $this->postJson('/api/v1/me/suggestions')
            ->assertOk()
            ->assertJsonPath('pantry.count', 3)
            ->assertJsonPath('meta.count', 2)
            ->assertJsonPath('candidates.0.title', 'Tropical Smoothie')
            ->assertJsonPath('candidates.0.match_summary.required_matched', 3)
            ->assertJsonMissing(['entered_name' => 'Onion']);
    }

    public function test_suggestion_generation_stays_deterministic_and_does_not_invoke_ai_explanations(): void
    {
        $user = User::factory()->create();
        $pineapple = $this->ingredient('Pineapple');
        $banana = $this->ingredient('Banana');

        $this->recipeTemplate(
            'Fruit Bowl',
            'breakfast',
            ['vegan', 'vegetarian'],
            [$pineapple, $banana]
        );

        $this->pantryItem($user, $pineapple);
        $this->pantryItem($user, $banana);

        $this->mock(RecipeExplanationProvider::class, function (MockInterface $mock): void {
            $mock->shouldNotReceive('generate');
        });

        Sanctum::actingAs($user);

        $this->postJson('/api/v1/me/suggestions')
            ->assertOk()
            ->assertJsonPath('candidates.0.title', 'Fruit Bowl')
            ->assertJsonPath('candidates.0.match_summary.required_matched', 2);
    }

    public function test_alias_and_canonical_pantry_matching_continues_to_work_for_suggestions(): void
    {
        $user = User::factory()->create();
        $chickpea = $this->ingredient('Chickpea');
        $lime = $this->ingredient('Lime');

        IngredientAlias::query()->create([
            'ingredient_id' => $chickpea->id,
            'alias' => 'Garbanzo Beans',
            'normalized_alias' => 'garbanzo beans',
            'locale' => 'en',
        ]);

        $this->recipeTemplate(
            'Protein Bowl',
            'light_meal',
            ['vegan', 'vegetarian'],
            [$chickpea, $lime]
        );

        $this->pantryItem($user, $chickpea, 'Garbanzo Beans');

        Sanctum::actingAs($user);

        $this->postJson('/api/v1/me/suggestions')
            ->assertOk()
            ->assertJsonPath('candidates.0.title', 'Protein Bowl')
            ->assertJsonPath('candidates.0.matched_ingredients.0.ingredient.slug', 'chickpea');
    }

    public function test_goal_filter_narrows_results_to_matching_template_types(): void
    {
        $user = User::factory()->create();
        $pineapple = $this->ingredient('Pineapple');
        $banana = $this->ingredient('Banana');
        $yogurt = $this->ingredient('Yogurt');

        $this->recipeTemplate(
            'Tropical Smoothie',
            'drink',
            ['vegetarian'],
            [$pineapple, $banana, $yogurt]
        );
        $this->recipeTemplate(
            'Fruit Bowl',
            'breakfast',
            ['vegan', 'vegetarian'],
            [$pineapple, $banana]
        );

        $this->pantryItem($user, $pineapple);
        $this->pantryItem($user, $banana);
        $this->pantryItem($user, $yogurt);

        Sanctum::actingAs($user);

        $this->postJson('/api/v1/me/suggestions', [
            'goal' => 'drink',
        ])
            ->assertOk()
            ->assertJsonPath('request.goal', 'drink')
            ->assertJsonPath('meta.count', 1)
            ->assertJsonPath('candidates.0.title', 'Tropical Smoothie');
    }

    public function test_dietary_preferences_exclude_incompatible_templates(): void
    {
        $user = User::factory()->create();
        $banana = $this->ingredient('Banana');
        $yogurt = $this->ingredient('Yogurt');
        $pineapple = $this->ingredient('Pineapple');

        $this->setFoodPreferences($user, [
            'dietary_patterns' => ['vegan'],
            'preferred_cuisines' => [],
            'disliked_ingredients' => [],
            'measurement_system' => 'metric',
        ]);

        $this->recipeTemplate(
            'Yogurt Mix',
            'breakfast',
            ['vegetarian'],
            [$yogurt, $banana],
            [$pineapple]
        );
        $this->recipeTemplate(
            'Fruit Bowl',
            'breakfast',
            ['vegan', 'vegetarian'],
            [$pineapple, $banana]
        );

        $this->pantryItem($user, $banana);
        $this->pantryItem($user, $yogurt);
        $this->pantryItem($user, $pineapple);

        Sanctum::actingAs($user);

        $this->postJson('/api/v1/me/suggestions')
            ->assertOk()
            ->assertJsonCount(1, 'candidates')
            ->assertJsonPath('candidates.0.title', 'Fruit Bowl')
            ->assertJsonMissing(['title' => 'Yogurt Mix']);
    }

    public function test_available_substitutions_are_returned_for_missing_ingredients(): void
    {
        $user = User::factory()->create();
        $pineapple = $this->ingredient('Pineapple');
        $banana = $this->ingredient('Banana');
        $yogurt = $this->ingredient('Yogurt');
        $mango = $this->ingredient('Mango');

        $this->recipeTemplate(
            'Tropical Smoothie',
            'drink',
            ['vegetarian'],
            [$pineapple, $banana, $yogurt]
        );
        $this->substitution($banana, $mango, 'Mango keeps the smoothie sweet.');

        $this->pantryItem($user, $pineapple);
        $this->pantryItem($user, $yogurt);
        $this->pantryItem($user, $mango);

        Sanctum::actingAs($user);

        $this->postJson('/api/v1/me/suggestions')
            ->assertOk()
            ->assertJsonPath('candidates.0.title', 'Tropical Smoothie')
            ->assertJsonPath('candidates.0.missing_ingredients.0.ingredient.slug', 'banana')
            ->assertJsonPath('candidates.0.missing_ingredients.0.covered_by_substitution', true)
            ->assertJsonPath('candidates.0.substitutions.0.missing_ingredient.slug', 'banana')
            ->assertJsonPath('candidates.0.substitutions.0.available_substitutes.0.ingredient.slug', 'mango');
    }

    public function test_scoring_order_is_deterministic_and_testable(): void
    {
        $user = User::factory()->create();
        $pineapple = $this->ingredient('Pineapple');
        $banana = $this->ingredient('Banana');
        $yogurt = $this->ingredient('Yogurt');
        $onion = $this->ingredient('Onion');
        $lime = $this->ingredient('Lime');

        $this->recipeTemplate(
            'Tropical Smoothie',
            'drink',
            ['vegetarian'],
            [$pineapple, $banana, $yogurt]
        );
        $this->recipeTemplate(
            'Yogurt Mix',
            'breakfast',
            ['vegetarian'],
            [$yogurt, $banana],
            [$pineapple]
        );
        $this->recipeTemplate(
            'Pineapple Salsa',
            'snack',
            ['vegan', 'vegetarian'],
            [$pineapple, $onion, $lime]
        );

        $this->pantryItem($user, $pineapple);
        $this->pantryItem($user, $banana);
        $this->pantryItem($user, $yogurt);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/me/suggestions')
            ->assertOk()
            ->assertJsonPath('candidates.0.title', 'Tropical Smoothie')
            ->assertJsonPath('candidates.1.title', 'Yogurt Mix')
            ->assertJsonPath('candidates.2.title', 'Pineapple Salsa');

        $scores = array_column($response->json('candidates'), 'score');

        $this->assertSame($scores, collect($scores)->sortDesc()->values()->all());
    }

    public function test_selected_pantry_item_ids_must_belong_to_the_current_user(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $pineapple = $this->ingredient('Pineapple');
        $banana = $this->ingredient('Banana');

        $this->recipeTemplate(
            'Fruit Bowl',
            'breakfast',
            ['vegan', 'vegetarian'],
            [$pineapple, $banana]
        );

        $ownedItem = $this->pantryItem($user, $pineapple);
        $otherUsersItem = $this->pantryItem($otherUser, $banana);

        Sanctum::actingAs($user);

        $this->postJson('/api/v1/me/suggestions', [
            'pantry_item_ids' => [$ownedItem->id, $otherUsersItem->id],
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('pantry_item_ids');
    }

    public function test_suggestion_request_normalizes_recipe_type_alias_and_selected_item_ids_in_the_response_payload(): void
    {
        $user = User::factory()->create();
        $pineapple = $this->ingredient('Pineapple');
        $banana = $this->ingredient('Banana');

        $this->recipeTemplate(
            'Fruit Bowl',
            'drink',
            ['vegan', 'vegetarian'],
            [$pineapple, $banana]
        );

        $firstItem = $this->pantryItem($user, $pineapple);
        $secondItem = $this->pantryItem($user, $banana);

        Sanctum::actingAs($user);

        $this->postJson('/api/v1/me/suggestions', [
            'recipe_type' => '  DRINK  ',
            'pantry_item_ids' => [
                " {$firstItem->id} ",
                $secondItem->id,
                $firstItem->id,
            ],
            'limit' => 2,
        ])
            ->assertOk()
            ->assertJsonPath('request.goal', 'drink')
            ->assertJsonPath('request.limit', 2)
            ->assertJsonPath('request.pantry_item_ids.0', $firstItem->id)
            ->assertJsonPath('request.pantry_item_ids.1', $secondItem->id)
            ->assertJsonCount(2, 'request.pantry_item_ids');
    }

    public function test_pairing_signals_help_surface_partial_matches(): void
    {
        $user = User::factory()->create();
        $pineapple = $this->ingredient('Pineapple');
        $banana = $this->ingredient('Banana');
        $yogurt = $this->ingredient('Yogurt');

        $this->recipeTemplate(
            'Tropical Smoothie',
            'drink',
            ['vegetarian'],
            [$pineapple, $banana, $yogurt]
        );
        $this->pairing($banana, $pineapple, 5, 'Classic smoothie pairing.');

        $this->pantryItem($user, $pineapple);
        $this->pantryItem($user, $yogurt);

        Sanctum::actingAs($user);

        $this->postJson('/api/v1/me/suggestions')
            ->assertOk()
            ->assertJsonPath('candidates.0.title', 'Tropical Smoothie')
            ->assertJsonPath('candidates.0.pairing_signals.0.target_ingredient.slug', 'banana')
            ->assertJsonPath('candidates.0.pairing_signals.0.pantry_ingredient.slug', 'pineapple');
    }

    protected function ingredient(string $name, ?string $slug = null): Ingredient
    {
        return Ingredient::factory()->create([
            'name' => $name,
            'slug' => $slug ?? Str::slug($name),
            'status' => ContentStatus::Published,
        ]);
    }

    protected function pantryItem(
        User $user,
        Ingredient $ingredient,
        ?string $enteredName = null,
        PantryItemStatus $status = PantryItemStatus::Active
    ): PantryItem {
        return PantryItem::query()->create([
            'user_id' => $user->id,
            'ingredient_id' => $ingredient->id,
            'entered_name' => $enteredName ?? $ingredient->name,
            'status' => $status,
        ]);
    }

    protected function recipeTemplate(
        string $title,
        string $recipeType,
        array $dietaryPatterns,
        array $requiredIngredients,
        array $optionalIngredients = []
    ): RecipeTemplate {
        $template = RecipeTemplate::factory()->create([
            'title' => $title,
            'slug' => Str::slug($title),
            'recipe_type' => $recipeType,
            'dietary_patterns' => $dietaryPatterns,
            'status' => ContentStatus::Published,
        ]);

        foreach ($requiredIngredients as $index => $ingredient) {
            RecipeTemplateIngredient::query()->create([
                'recipe_template_id' => $template->id,
                'ingredient_id' => $ingredient->id,
                'is_required' => true,
                'sort_order' => $index + 1,
            ]);
        }

        foreach ($optionalIngredients as $index => $ingredient) {
            RecipeTemplateIngredient::query()->create([
                'recipe_template_id' => $template->id,
                'ingredient_id' => $ingredient->id,
                'is_required' => false,
                'sort_order' => count($requiredIngredients) + $index + 1,
            ]);
        }

        return $template;
    }

    protected function substitution(
        Ingredient $ingredient,
        Ingredient $substituteIngredient,
        ?string $note = null
    ): Substitution {
        return Substitution::query()->create([
            'ingredient_id' => $ingredient->id,
            'substitute_ingredient_id' => $substituteIngredient->id,
            'note' => $note,
            'status' => ContentStatus::Published,
        ]);
    }

    protected function pairing(
        Ingredient $ingredient,
        Ingredient $pairedIngredient,
        int $strength,
        ?string $note = null
    ): Pairing {
        return Pairing::query()->create([
            'ingredient_id' => $ingredient->id,
            'paired_ingredient_id' => $pairedIngredient->id,
            'strength' => $strength,
            'note' => $note,
            'status' => ContentStatus::Published,
        ]);
    }

    protected function setFoodPreferences(User $user, array $preferences): void
    {
        UserPreference::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'key' => UserPreference::FOOD_PREFERENCES_KEY,
            ],
            [
                'value' => $preferences,
            ]
        );
    }
}
