<?php

namespace App\Modules\Contributions\Domain\Policies;

use App\Modules\Contributions\Domain\Models\Contribution;
use App\Modules\Users\Domain\Models\User;

class ContributionPolicy
{
    public function create(User $user): bool
    {
        return true;
    }

    public function report(User $user, Contribution $contribution): bool
    {
        return true;
    }

    public function viewAny(User $user): bool
    {
        return $user->canModerate();
    }

    public function view(User $user, Contribution $contribution): bool
    {
        return $user->canModerate();
    }

    public function moderate(User $user, Contribution $contribution): bool
    {
        return $user->canModerate();
    }
}
