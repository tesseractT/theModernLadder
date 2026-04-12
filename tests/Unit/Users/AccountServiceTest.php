<?php

namespace Tests\Unit\Users;

use App\Modules\Users\Application\DTO\UpdateFoodPreferencesData;
use App\Modules\Users\Application\DTO\UpdateProfileData;
use App\Modules\Users\Application\Services\AccountService;
use App\Modules\Users\Domain\Models\User;
use App\Modules\Users\Domain\Models\UserPreference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_updates_profile_fields_from_a_typed_payload(): void
    {
        $user = User::factory()->create();
        $service = app(AccountService::class);

        $updated = $service->updateProfile(
            $user,
            UpdateProfileData::fromValidated([
                'display_name' => 'Updated Name',
                'timezone' => 'Europe/London',
            ])
        );

        $this->assertSame('Updated Name', $updated->profile->display_name);
        $this->assertSame('Europe/London', $updated->profile->timezone);
    }

    public function test_it_merges_food_preferences_from_a_typed_payload_without_dropping_untouched_values(): void
    {
        $user = User::factory()->create();
        UserPreference::query()->create([
            'user_id' => $user->id,
            'key' => UserPreference::FOOD_PREFERENCES_KEY,
            'value' => [
                'dietary_patterns' => ['vegetarian'],
                'preferred_cuisines' => ['Italian'],
                'disliked_ingredients' => ['anchovy'],
                'measurement_system' => 'metric',
            ],
        ]);
        $service = app(AccountService::class);

        $updated = $service->updateFoodPreferences(
            $user,
            UpdateFoodPreferencesData::fromValidated([
                'measurement_system' => 'imperial',
            ])
        );

        $this->assertSame('imperial', $updated->resolvedFoodPreferences()['measurement_system']);
        $this->assertSame(['Italian'], $updated->resolvedFoodPreferences()['preferred_cuisines']);
        $this->assertSame(['anchovy'], $updated->resolvedFoodPreferences()['disliked_ingredients']);
    }
}
