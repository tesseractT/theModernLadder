<?php

namespace Tests\Feature\Ingredients;

use App\Modules\Ingredients\Domain\Models\Ingredient;
use App\Modules\Ingredients\Domain\Models\IngredientAlias;
use App\Modules\Users\Domain\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class IngredientSearchApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_search_ingredients_by_canonical_name(): void
    {
        Sanctum::actingAs(User::factory()->create());

        Ingredient::factory()->create([
            'name' => 'Pineapple',
            'slug' => 'pineapple',
        ]);

        Ingredient::factory()->create([
            'name' => 'Mango',
            'slug' => 'mango',
        ]);

        $this->getJson('/api/v1/ingredients/search?q=pineapple')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Pineapple')
            ->assertJsonPath('data.0.slug', 'pineapple');
    }

    public function test_authenticated_user_can_match_aliases_and_receive_unique_canonical_results(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $ingredient = Ingredient::factory()->create([
            'name' => 'Pineapple',
            'slug' => 'pineapple',
        ]);

        IngredientAlias::query()->create([
            'ingredient_id' => $ingredient->id,
            'alias' => 'Fresh Pineapple',
            'normalized_alias' => 'fresh pineapple',
            'locale' => 'en',
        ]);

        IngredientAlias::query()->create([
            'ingredient_id' => $ingredient->id,
            'alias' => 'Pineapple Chunks',
            'normalized_alias' => 'pineapple chunks',
            'locale' => 'en',
        ]);

        $this->getJson('/api/v1/ingredients/search?q=pine')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $ingredient->id)
            ->assertJsonPath('data.0.name', 'Pineapple');
    }

    public function test_unauthenticated_ingredient_search_is_rejected(): void
    {
        $this->getJson('/api/v1/ingredients/search?q=pineapple')
            ->assertUnauthorized();
    }
}
