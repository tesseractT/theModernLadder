<?php

namespace App\Modules\Pantry\Policies;

use App\Modules\Pantry\Domain\Models\PantryItem;
use App\Modules\Users\Domain\Models\User;

class PantryItemPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function view(User $user, PantryItem $pantryItem): bool
    {
        return $pantryItem->user_id === $user->getAuthIdentifier();
    }

    public function update(User $user, PantryItem $pantryItem): bool
    {
        return $pantryItem->user_id === $user->getAuthIdentifier();
    }

    public function delete(User $user, PantryItem $pantryItem): bool
    {
        return $pantryItem->user_id === $user->getAuthIdentifier();
    }
}
