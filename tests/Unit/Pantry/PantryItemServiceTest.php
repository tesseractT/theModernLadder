<?php

namespace Tests\Unit\Pantry;

use App\Modules\Ingredients\Domain\Models\Ingredient;
use App\Modules\Pantry\Application\DTO\StorePantryItemData;
use App\Modules\Pantry\Application\DTO\UpdatePantryItemData;
use App\Modules\Pantry\Application\Services\PantryItemService;
use App\Modules\Pantry\Domain\Models\PantryItem;
use App\Modules\Users\Domain\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PantryItemServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_pantry_item_from_a_typed_store_payload(): void
    {
        $user = User::factory()->create();
        $ingredient = Ingredient::factory()->create();
        $service = app(PantryItemService::class);

        $pantryItem = $service->createForUser(
            $user,
            new StorePantryItemData(
                ingredientId: $ingredient->id,
                quantity: 2.5,
                unit: 'cups',
                note: 'For smoothies',
                expiresOn: '2026-04-15',
            )
        );

        $this->assertSame($user->id, $pantryItem->user_id);
        $this->assertSame($ingredient->id, $pantryItem->ingredient_id);
        $this->assertSame('cups', $pantryItem->unit);
        $this->assertSame('For smoothies', $pantryItem->note);
    }

    public function test_it_updates_only_the_attributes_present_in_the_typed_patch_payload(): void
    {
        $user = User::factory()->create();
        $ingredient = Ingredient::factory()->create();
        $pantryItem = PantryItem::query()->create([
            'user_id' => $user->id,
            'ingredient_id' => $ingredient->id,
            'entered_name' => $ingredient->name,
            'quantity' => 1,
            'unit' => 'piece',
            'note' => 'Original note',
        ]);
        $service = app(PantryItemService::class);

        $updated = $service->update(
            $pantryItem,
            UpdatePantryItemData::fromValidated([
                'unit' => 'slices',
                'note' => null,
            ])
        );

        $this->assertSame('slices', $updated->unit);
        $this->assertNull($updated->note);
        $this->assertSame('1.00', $updated->quantity);
    }

    public function test_duplicate_create_failure_stays_attached_to_the_ingredient_field(): void
    {
        $user = User::factory()->create();
        $ingredient = Ingredient::factory()->create();
        PantryItem::query()->create([
            'user_id' => $user->id,
            'ingredient_id' => $ingredient->id,
            'entered_name' => $ingredient->name,
        ]);
        $service = app(PantryItemService::class);

        try {
            $service->createForUser(
                $user,
                new StorePantryItemData(
                    ingredientId: $ingredient->id,
                    quantity: null,
                    unit: null,
                    note: null,
                    expiresOn: null,
                )
            );

            $this->fail('Expected a validation exception for duplicate pantry creation.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('ingredient_id', $exception->errors());
        }
    }
}
