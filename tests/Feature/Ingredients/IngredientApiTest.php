<?php

namespace Tests\Feature\Ingredients;

use App\Modules\Ingredients\Domain\Models\Ingredient;
use App\Modules\Ingredients\Domain\Models\IngredientAlias;
use App\Modules\Shared\Domain\Enums\ContentStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IngredientApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_lists_only_published_ingredients_and_supports_alias_search(): void
    {
        $ingredient = Ingredient::factory()->create([
            'name' => 'Tomato',
            'slug' => 'tomato',
        ]);

        IngredientAlias::query()->create([
            'ingredient_id' => $ingredient->id,
            'alias' => 'Roma Tomato',
            'normalized_alias' => 'roma tomato',
            'locale' => 'en',
        ]);

        Ingredient::factory()->create([
            'name' => 'Anchovy',
            'slug' => 'anchovy',
            'status' => ContentStatus::Archived,
        ]);

        $response = $this->getJson('/api/v1/ingredients?search=roma');

        $response
            ->assertOk()
            ->assertJsonPath('data.0.slug', 'tomato')
            ->assertJsonMissing(['slug' => 'anchovy']);
    }

    public function test_it_shows_a_single_published_ingredient(): void
    {
        $ingredient = Ingredient::factory()->create([
            'name' => 'Basil',
            'slug' => 'basil',
        ]);

        IngredientAlias::query()->create([
            'ingredient_id' => $ingredient->id,
            'alias' => 'Sweet Basil',
            'normalized_alias' => 'sweet basil',
            'locale' => 'en',
        ]);

        $response = $this->getJson('/api/v1/ingredients/basil');

        $response
            ->assertOk()
            ->assertJsonPath('slug', 'basil')
            ->assertJsonPath('aliases.0.alias', 'Sweet Basil');
    }

    public function test_it_returns_not_found_for_unpublished_ingredients(): void
    {
        Ingredient::factory()->create([
            'slug' => 'hidden-ingredient',
            'status' => ContentStatus::Archived,
        ]);

        $this->getJson('/api/v1/ingredients/hidden-ingredient')
            ->assertNotFound();
    }
}
