<?php

namespace App\Modules\Pantry\Application\Services;

use App\Modules\Ingredients\Domain\Models\Ingredient;
use App\Modules\Pantry\Application\DTO\StorePantryItemData;
use App\Modules\Pantry\Application\DTO\UpdatePantryItemData;
use App\Modules\Pantry\Domain\Models\PantryItem;
use App\Modules\Users\Domain\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class PantryItemService
{
    public function paginateForUser(User $user, int $perPage): LengthAwarePaginator
    {
        return PantryItem::query()
            ->ownedBy($user)
            ->active()
            ->with('ingredient')
            ->orderByDesc('updated_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function createForUser(User $user, StorePantryItemData $payload): PantryItem
    {
        $ingredient = $this->resolveIngredient($payload->ingredientId);

        $this->ensureNotDuplicated($user, $ingredient->id);

        return PantryItem::query()->create([
            'user_id' => $user->id,
            'ingredient_id' => $ingredient->id,
            'entered_name' => $ingredient->name,
            'quantity' => $payload->quantity,
            'unit' => $payload->unit,
            'note' => $payload->note,
            'expires_on' => $payload->expiresOn,
        ])->load('ingredient');
    }

    public function update(PantryItem $pantryItem, UpdatePantryItemData $payload): PantryItem
    {
        $pantryItem->fill($payload->attributes());
        $pantryItem->save();

        return $pantryItem->fresh(['ingredient']);
    }

    public function delete(PantryItem $pantryItem): void
    {
        $pantryItem->delete();
    }

    protected function resolveIngredient(string $ingredientId): Ingredient
    {
        return Ingredient::query()
            ->published()
            ->findOrFail($ingredientId);
    }

    protected function ensureNotDuplicated(User $user, string $ingredientId): void
    {
        $exists = PantryItem::query()
            ->ownedBy($user)
            ->active()
            ->where('ingredient_id', $ingredientId)
            ->exists();

        if (! $exists) {
            return;
        }

        throw ValidationException::withMessages([
            'ingredient_id' => ['This ingredient is already in your pantry.'],
        ]);
    }
}
