<?php

namespace App\Modules\Users\Application\Services;

use App\Modules\Users\Domain\Models\Profile;
use App\Modules\Users\Domain\Models\User;
use App\Modules\Users\Domain\Models\UserPreference;

class AccountService
{
    public function initialize(User $user, ?string $displayName = null): User
    {
        $this->ensureProfile($user, $displayName);
        $this->ensureFoodPreferences($user);

        return $this->load($user);
    }

    public function load(User $user): User
    {
        $this->ensureProfile($user);
        $this->ensureFoodPreferences($user);

        return $user->fresh(['profile', 'foodPreference']);
    }

    public function updateProfile(User $user, array $attributes): User
    {
        $profile = $this->ensureProfile($user);

        $profile->fill($attributes);
        $profile->save();

        return $this->load($user);
    }

    public function updateFoodPreferences(User $user, array $attributes): User
    {
        $preference = $this->ensureFoodPreferences($user);

        $preference->forceFill([
            'value' => array_replace($user->resolvedFoodPreferences(), $attributes),
        ])->save();

        return $this->load($user);
    }

    public function ensureProfile(User $user, ?string $displayName = null): Profile
    {
        return Profile::query()->firstOrCreate(
            ['user_id' => $user->id],
            [
                'display_name' => $displayName ?? $user->defaultDisplayName(),
                'locale' => config('app.locale'),
            ]
        );
    }

    public function ensureFoodPreferences(User $user): UserPreference
    {
        return UserPreference::query()->firstOrCreate(
            [
                'user_id' => $user->id,
                'key' => UserPreference::FOOD_PREFERENCES_KEY,
            ],
            [
                'value' => config('user_preferences.defaults', []),
            ]
        );
    }
}
