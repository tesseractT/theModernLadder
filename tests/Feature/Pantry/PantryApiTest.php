<?php

namespace Tests\Feature\Pantry;

use App\Modules\Ingredients\Domain\Models\Ingredient;
use App\Modules\Pantry\Domain\Models\PantryItem;
use App\Modules\Users\Domain\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PantryApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_list_only_their_pantry_items(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $ingredient = Ingredient::factory()->create([
            'name' => 'Pineapple',
            'slug' => 'pineapple',
        ]);
        $otherIngredient = Ingredient::factory()->create([
            'name' => 'Tomato',
            'slug' => 'tomato',
        ]);

        PantryItem::query()->create([
            'user_id' => $user->id,
            'ingredient_id' => $ingredient->id,
            'entered_name' => 'Pineapple',
        ]);

        PantryItem::query()->create([
            'user_id' => $otherUser->id,
            'ingredient_id' => $otherIngredient->id,
            'entered_name' => 'Tomato',
        ]);

        Sanctum::actingAs($user);

        $this->getJson('/api/v1/me/pantry')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.ingredient.slug', 'pineapple')
            ->assertJsonMissing(['slug' => 'tomato']);
    }

    public function test_authenticated_user_can_add_a_pantry_item(): void
    {
        $user = User::factory()->create();
        $ingredient = Ingredient::factory()->create([
            'name' => 'Pineapple',
            'slug' => 'pineapple',
        ]);

        Sanctum::actingAs($user);

        $this->postJson('/api/v1/me/pantry', [
            'ingredient_id' => $ingredient->id,
            'quantity' => 2.5,
            'unit' => 'cups',
            'note' => 'For smoothies',
            'expires_on' => '2026-04-15',
        ])
            ->assertCreated()
            ->assertJsonPath('pantry_item.ingredient.id', $ingredient->id)
            ->assertJsonPath('pantry_item.entered_name', 'Pineapple')
            ->assertJsonPath('pantry_item.quantity', 2.5)
            ->assertJsonPath('pantry_item.unit', 'cups');

        $this->assertDatabaseHas('pantry_items', [
            'user_id' => $user->id,
            'ingredient_id' => $ingredient->id,
            'entered_name' => 'Pineapple',
            'unit' => 'cups',
        ]);
    }

    public function test_store_pantry_item_normalizes_trimmed_input_before_it_reaches_the_service_boundary(): void
    {
        $user = User::factory()->create();
        $ingredient = Ingredient::factory()->create([
            'name' => 'Pineapple',
            'slug' => 'pineapple',
        ]);

        Sanctum::actingAs($user);

        $this->postJson('/api/v1/me/pantry', [
            'ingredient_id' => " {$ingredient->id} ",
            'quantity' => '2.5',
            'unit' => ' cups ',
            'note' => '  For smoothies  ',
        ])
            ->assertCreated()
            ->assertJsonPath('pantry_item.unit', 'cups')
            ->assertJsonPath('pantry_item.note', 'For smoothies')
            ->assertJsonPath('pantry_item.quantity', 2.5);

        $this->assertDatabaseHas('pantry_items', [
            'user_id' => $user->id,
            'ingredient_id' => $ingredient->id,
            'unit' => 'cups',
            'note' => 'For smoothies',
        ]);
    }

    public function test_duplicate_add_returns_a_validation_error(): void
    {
        $user = User::factory()->create();
        $ingredient = Ingredient::factory()->create([
            'name' => 'Pineapple',
            'slug' => 'pineapple',
        ]);

        PantryItem::query()->create([
            'user_id' => $user->id,
            'ingredient_id' => $ingredient->id,
            'entered_name' => 'Pineapple',
        ]);

        Sanctum::actingAs($user);

        $this->postJson('/api/v1/me/pantry', [
            'ingredient_id' => $ingredient->id,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('ingredient_id');
    }

    public function test_authenticated_user_can_update_their_own_pantry_item(): void
    {
        $user = User::factory()->create();
        $ingredient = Ingredient::factory()->create([
            'name' => 'Pineapple',
            'slug' => 'pineapple',
        ]);

        $pantryItem = PantryItem::query()->create([
            'user_id' => $user->id,
            'ingredient_id' => $ingredient->id,
            'entered_name' => 'Pineapple',
            'quantity' => 1,
            'unit' => 'piece',
        ]);

        Sanctum::actingAs($user);

        $this->patchJson("/api/v1/me/pantry/{$pantryItem->id}", [
            'quantity' => 3,
            'unit' => 'slices',
            'note' => 'Cut and ready',
        ])
            ->assertOk()
            ->assertJsonPath('pantry_item.quantity', 3)
            ->assertJsonPath('pantry_item.unit', 'slices')
            ->assertJsonPath('pantry_item.note', 'Cut and ready');

        $this->assertDatabaseHas('pantry_items', [
            'id' => $pantryItem->id,
            'unit' => 'slices',
            'note' => 'Cut and ready',
        ]);
    }

    public function test_authenticated_user_can_delete_their_own_pantry_item(): void
    {
        $user = User::factory()->create();
        $ingredient = Ingredient::factory()->create([
            'name' => 'Pineapple',
            'slug' => 'pineapple',
        ]);

        $pantryItem = PantryItem::query()->create([
            'user_id' => $user->id,
            'ingredient_id' => $ingredient->id,
            'entered_name' => 'Pineapple',
        ]);

        Sanctum::actingAs($user);

        $this->deleteJson("/api/v1/me/pantry/{$pantryItem->id}")
            ->assertOk()
            ->assertJsonPath('message', 'Pantry item removed successfully.');

        $this->assertSoftDeleted('pantry_items', [
            'id' => $pantryItem->id,
        ]);
    }

    public function test_unauthenticated_pantry_access_is_rejected(): void
    {
        $ingredient = Ingredient::factory()->create();
        $pantryItem = PantryItem::query()->create([
            'user_id' => User::factory()->create()->id,
            'ingredient_id' => $ingredient->id,
            'entered_name' => $ingredient->name,
        ]);

        $this->getJson('/api/v1/me/pantry')->assertUnauthorized();
        $this->postJson('/api/v1/me/pantry', ['ingredient_id' => $ingredient->id])->assertUnauthorized();
        $this->patchJson("/api/v1/me/pantry/{$pantryItem->id}", ['note' => 'Nope'])->assertUnauthorized();
        $this->deleteJson("/api/v1/me/pantry/{$pantryItem->id}")->assertUnauthorized();
    }

    public function test_user_cannot_modify_another_users_pantry_item(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $ingredient = Ingredient::factory()->create([
            'name' => 'Pineapple',
            'slug' => 'pineapple',
        ]);

        $otherUsersPantryItem = PantryItem::query()->create([
            'user_id' => $otherUser->id,
            'ingredient_id' => $ingredient->id,
            'entered_name' => 'Pineapple',
        ]);

        Sanctum::actingAs($user);

        $this->patchJson("/api/v1/me/pantry/{$otherUsersPantryItem->id}", [
            'note' => 'Should not work',
        ])->assertNotFound();

        $this->deleteJson("/api/v1/me/pantry/{$otherUsersPantryItem->id}")
            ->assertNotFound();
    }
}
