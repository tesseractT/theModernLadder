<?php

namespace App\Modules\Pantry\Application\Services;

use App\Modules\Ingredients\Domain\Models\Ingredient;
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

    public function createForUser(User $user, Ingredient $ingredient, array $attributes): PantryItem
    {
        $this->ensureNotDuplicated($user, $ingredient->id);

        return PantryItem::query()->create([
            'user_id' => $user->id,
            'ingredient_id' => $ingredient->id,
            'entered_name' => $ingredient->name,
            'quantity' => $attributes['quantity'] ?? null,
            'unit' => $attributes['unit'] ?? null,
            'note' => $attributes['note'] ?? null,
            'expires_on' => $attributes['expires_on'] ?? null,
        ])->load('ingredient');
    }

    public function updateForUser(User $user, string $pantryItemId, array $attributes): PantryItem
    {
        $pantryItem = $this->findForUser($user, $pantryItemId);

        $pantryItem->fill($attributes);
        $pantryItem->save();

        return $pantryItem->fresh(['ingredient']);
    }

    public function deleteForUser(User $user, string $pantryItemId): void
    {
        $this->findForUser($user, $pantryItemId)->delete();
    }

    public function findForUser(User $user, string $pantryItemId): PantryItem
    {
        return PantryItem::query()
            ->ownedBy($user)
            ->active()
            ->with('ingredient')
            ->findOrFail($pantryItemId);
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
