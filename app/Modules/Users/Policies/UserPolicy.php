<?php

namespace App\Modules\Users\Policies;

use App\Modules\Users\Domain\Models\User;

class UserPolicy
{
    public function view(User $actor, User $subject): bool
    {
        return $actor->is($subject);
    }

    public function update(User $actor, User $subject): bool
    {
        return $actor->is($subject);
    }
}
