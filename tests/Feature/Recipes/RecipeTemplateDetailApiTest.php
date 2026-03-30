<?php

namespace Tests\Feature\Recipes;

use App\Modules\Pantry\Domain\Models\PantryItem;
use App\Modules\Recipes\Domain\Models\RecipeTemplate;
use App\Modules\Recipes\Domain\Models\RecipeTemplateIngredient;
use App\Modules\Recipes\Domain\Models\RecipeTemplateStep;
use App\Modules\Shared\Domain\Enums\ContentStatus;
use App\Modules\Users\Domain\Models\User;
use Database\Seeders\StarterRecipeTemplateCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RecipeTemplateDetailApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_fetch_a_recipe_template_detail(): void
    {
        $this->seed(StarterRecipeTemplateCatalogSeeder::class);

        $user = User::factory()->create();
        $template = RecipeTemplate::query()->where('slug', 'pineapple-smoothie')->firstOrFail();

        $pineapple = $template->templateIngredients()->whereHas('ingredient', fn ($query) => $query->where('slug', 'pineapple'))->firstOrFail();
        $banana = $template->templateIngredients()->whereHas('ingredient', fn ($query) => $query->where('slug', 'banana'))->firstOrFail();
        $yogurt = $template->templateIngredients()->whereHas('ingredient', fn ($query) => $query->where('slug', 'yogurt'))->firstOrFail();

        $this->pantryItem($user, $pineapple->ingredient_id);
        $this->pantryItem($user, $banana->ingredient_id);
        $this->pantryItem($user, $yogurt->ingredient_id);

        Sanctum::actingAs($user);

        $this->getJson("/api/v1/recipes/templates/{$template->id}")
            ->assertOk()
            ->assertJsonPath('template.title', 'Pineapple Smoothie')
            ->assertJsonPath('template.recipe_type', 'drink')
            ->assertJsonPath('template.difficulty', 'easy')
            ->assertJsonPath('template.total_minutes', 10)
            ->assertJsonPath('pantry_fit.required_owned', 3)
            ->assertJsonPath('pantry_fit.can_make_with_current_pantry', true);
    }

    public function test_unauthenticated_recipe_template_detail_access_is_rejected(): void
    {
        $this->seed(StarterRecipeTemplateCatalogSeeder::class);

        $template = RecipeTemplate::query()->where('slug', 'pineapple-smoothie')->firstOrFail();

        $this->getJson("/api/v1/recipes/templates/{$template->id}")
            ->assertUnauthorized();
    }

    public function test_unpublished_or_non_readable_templates_are_not_exposed(): void
    {
        $user = User::factory()->create();

        $template = RecipeTemplate::factory()->create([
            'title' => 'Private Template',
            'slug' => 'private-template',
            'status' => ContentStatus::Draft,
        ]);

        Sanctum::actingAs($user);

        $this->getJson("/api/v1/recipes/templates/{$template->id}")
            ->assertNotFound();
    }

    public function test_detail_payload_includes_structured_ingredients_and_steps(): void
    {
        $this->seed(StarterRecipeTemplateCatalogSeeder::class);

        $user = User::factory()->create();
        $template = RecipeTemplate::query()->where('slug', 'pineapple-salsa')->firstOrFail();

        Sanctum::actingAs($user);

        $this->getJson("/api/v1/recipes/templates/{$template->id}")
            ->assertOk()
            ->assertJsonPath('ingredients.required.0.position', 1)
            ->assertJsonPath('ingredients.required.0.ingredient.slug', 'pineapple')
            ->assertJsonPath('ingredients.optional.0.ingredient.slug', 'cilantro')
            ->assertJsonPath('steps.0.position', 1)
            ->assertJsonPath('steps.0.instruction', 'Dice the pineapple, tomato, and onion into small even pieces.');
    }

    public function test_pantry_fit_overlay_correctly_marks_owned_and_missing_ingredients(): void
    {
        $this->seed(StarterRecipeTemplateCatalogSeeder::class);

        $user = User::factory()->create();
        $template = RecipeTemplate::query()->where('slug', 'pineapple-smoothie')->firstOrFail();

        $this->pantryItemForTemplateIngredient($user, $template, 'pineapple');
        $this->pantryItemForTemplateIngredient($user, $template, 'yogurt');

        Sanctum::actingAs($user);

        $this->getJson("/api/v1/recipes/templates/{$template->id}")
            ->assertOk()
            ->assertJsonPath('pantry_fit.required_total', 3)
            ->assertJsonPath('pantry_fit.required_owned', 2)
            ->assertJsonPath('pantry_fit.required_missing', 1)
            ->assertJsonPath('pantry_fit.can_make_with_current_pantry', false)
            ->assertJsonPath('ingredients.required.1.ingredient.slug', 'banana')
            ->assertJsonPath('ingredients.required.1.is_owned', false);
    }

    public function test_substitutions_are_included_when_the_users_pantry_can_cover_missing_ingredients(): void
    {
        $this->seed(StarterRecipeTemplateCatalogSeeder::class);

        $user = User::factory()->create();
        $template = RecipeTemplate::query()->where('slug', 'pineapple-smoothie')->firstOrFail();

        $this->pantryItemForTemplateIngredient($user, $template, 'pineapple');
        $this->pantryItemForTemplateIngredient($user, $template, 'yogurt');
        $this->pantryItemBySlug($user, 'mango');

        Sanctum::actingAs($user);

        $this->getJson("/api/v1/recipes/templates/{$template->id}")
            ->assertOk()
            ->assertJsonPath('pantry_fit.can_make_after_substitutions', true)
            ->assertJsonPath('substitutions.0.for_ingredient.slug', 'banana')
            ->assertJsonPath('substitutions.0.available_substitutes.0.ingredient.slug', 'mango')
            ->assertJsonPath('ingredients.required.1.substitutions.0.ingredient.slug', 'mango');
    }

    public function test_template_detail_remains_consistent_with_starter_catalog_data(): void
    {
        $this->seed(StarterRecipeTemplateCatalogSeeder::class);

        $user = User::factory()->create();
        $template = RecipeTemplate::query()->where('slug', 'simple-green-salad')->firstOrFail();

        Sanctum::actingAs($user);

        $this->getJson("/api/v1/recipes/templates/{$template->id}")
            ->assertOk()
            ->assertJsonPath('template.title', 'Simple Green Salad')
            ->assertJsonPath('template.recipe_type', 'light_meal')
            ->assertJsonPath('template.servings', 2)
            ->assertJsonPath('ingredients.required.0.ingredient.slug', 'lettuce')
            ->assertJsonPath('ingredients.optional.0.ingredient.slug', 'onion')
            ->assertJsonPath('steps.2.instruction', 'Dress with lime and add onion if using.');
    }

    public function test_starter_catalog_load_path_is_rerunnable_without_duplication(): void
    {
        Artisan::call('db:seed', [
            '--class' => StarterRecipeTemplateCatalogSeeder::class,
        ]);
        Artisan::call('db:seed', [
            '--class' => StarterRecipeTemplateCatalogSeeder::class,
        ]);

        $this->assertSame(6, RecipeTemplate::query()->count());
        $this->assertSame(29, RecipeTemplateIngredient::query()->count());
        $this->assertSame(18, RecipeTemplateStep::query()->count());
        $this->assertSame(
            1,
            RecipeTemplate::query()->where('slug', 'pineapple-smoothie')->count()
        );
    }

    protected function pantryItemForTemplateIngredient(
        User $user,
        RecipeTemplate $template,
        string $ingredientSlug
    ): void {
        $ingredientId = $template->templateIngredients()
            ->whereHas('ingredient', fn ($query) => $query->where('slug', $ingredientSlug))
            ->value('ingredient_id');

        $this->pantryItem($user, $ingredientId);
    }

    protected function pantryItemBySlug(User $user, string $ingredientSlug): void
    {
        $ingredientId = RecipeTemplateIngredient::query()
            ->whereHas('ingredient', fn ($query) => $query->where('slug', $ingredientSlug))
            ->value('ingredient_id');

        $this->pantryItem($user, $ingredientId);
    }

    protected function pantryItem(User $user, string $ingredientId): void
    {
        PantryItem::query()->create([
            'user_id' => $user->id,
            'ingredient_id' => $ingredientId,
            'entered_name' => 'Seeded Ingredient',
        ]);
    }
}
