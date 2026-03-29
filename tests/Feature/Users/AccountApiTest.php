<?php

namespace Tests\Feature\Users;

use App\Modules\Users\Domain\Models\Profile;
use App\Modules\Users\Domain\Models\User;
use App\Modules\Users\Domain\Models\UserPreference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AccountApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_fetch_me(): void
    {
        $user = User::factory()->create([
            'email' => 'harper@example.com',
        ]);

        Sanctum::actingAs($user);

        $this->getJson('/api/v1/me')
            ->assertOk()
            ->assertJsonPath('user.email', 'harper@example.com')
            ->assertJsonPath('user.profile.display_name', 'Harper')
            ->assertJsonPath('user.preferences.measurement_system', 'metric');
    }

    public function test_authenticated_user_can_update_own_profile(): void
    {
        $user = User::factory()->create();

        Profile::query()->create([
            'user_id' => $user->id,
            'display_name' => 'Original Name',
            'locale' => 'en',
        ]);

        Sanctum::actingAs($user);

        $this->patchJson('/api/v1/me/profile', [
            'display_name' => 'Updated Name',
            'bio' => 'Home cook and curious eater.',
            'locale' => 'en-GB',
            'timezone' => 'Europe/London',
            'country_code' => 'gb',
        ])
            ->assertOk()
            ->assertJsonPath('user.profile.display_name', 'Updated Name')
            ->assertJsonPath('user.profile.country_code', 'GB');

        $this->assertDatabaseHas('profiles', [
            'user_id' => $user->id,
            'display_name' => 'Updated Name',
            'country_code' => 'GB',
            'timezone' => 'Europe/London',
        ]);
    }

    public function test_authenticated_user_can_update_own_preferences(): void
    {
        $user = User::factory()->create();

        UserPreference::query()->create([
            'user_id' => $user->id,
            'key' => UserPreference::FOOD_PREFERENCES_KEY,
            'value' => [
                'dietary_patterns' => [],
                'preferred_cuisines' => ['Italian'],
                'disliked_ingredients' => [],
                'measurement_system' => 'metric',
            ],
        ]);

        Sanctum::actingAs($user);

        $this->patchJson('/api/v1/me/preferences', [
            'dietary_patterns' => ['vegetarian', 'halal', 'vegetarian'],
            'preferred_cuisines' => ['Levantine', 'Japanese'],
            'disliked_ingredients' => ['anchovy', 'olives'],
            'measurement_system' => 'imperial',
        ])
            ->assertOk()
            ->assertJsonPath('user.preferences.dietary_patterns.0', 'vegetarian')
            ->assertJsonPath('user.preferences.dietary_patterns.1', 'halal')
            ->assertJsonPath('user.preferences.measurement_system', 'imperial');

        $preference = UserPreference::query()
            ->where('user_id', $user->id)
            ->where('key', UserPreference::FOOD_PREFERENCES_KEY)
            ->firstOrFail();

        $this->assertSame(
            ['vegetarian', 'halal'],
            $preference->value['dietary_patterns']
        );
        $this->assertSame('imperial', $preference->value['measurement_system']);
    }

    public function test_unauthenticated_access_to_protected_account_endpoints_is_rejected(): void
    {
        $this->getJson('/api/v1/me')->assertUnauthorized();
        $this->patchJson('/api/v1/me/profile', ['display_name' => 'Nope'])->assertUnauthorized();
        $this->patchJson('/api/v1/me/preferences', ['measurement_system' => 'metric'])->assertUnauthorized();
        $this->postJson('/api/v1/auth/logout')->assertUnauthorized();
    }
}
