<?php

namespace Tests\Feature\Users;

use App\Modules\Users\Domain\Enums\UserStatus;
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

    public function test_preferences_patch_only_updates_provided_keys_and_preserves_existing_values(): void
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

        Sanctum::actingAs($user);

        $this->patchJson('/api/v1/me/preferences', [
            'measurement_system' => 'imperial',
        ])
            ->assertOk()
            ->assertJsonPath('user.preferences.measurement_system', 'imperial')
            ->assertJsonPath('user.preferences.preferred_cuisines.0', 'Italian')
            ->assertJsonPath('user.preferences.disliked_ingredients.0', 'anchovy');
    }

    public function test_unauthenticated_access_to_protected_account_endpoints_is_rejected(): void
    {
        $this->withHeader('X-Request-Id', 'unauthenticated-account')
            ->getJson('/api/v1/me')
            ->assertUnauthorized()
            ->assertHeader('X-Request-Id', 'unauthenticated-account')
            ->assertJsonPath('code', 'unauthenticated')
            ->assertJsonPath('message', 'Authentication required.');

        $this->patchJson('/api/v1/me/profile', ['display_name' => 'Nope'])
            ->assertUnauthorized()
            ->assertJsonPath('code', 'unauthenticated');

        $this->patchJson('/api/v1/me/preferences', ['measurement_system' => 'metric'])
            ->assertUnauthorized()
            ->assertJsonPath('code', 'unauthenticated');

        $this->postJson('/api/v1/auth/logout')
            ->assertUnauthorized()
            ->assertJsonPath('code', 'unauthenticated');
    }

    public function test_suspended_users_with_existing_tokens_receive_the_standardized_forbidden_response(): void
    {
        $user = User::factory()->create([
            'status' => UserStatus::Suspended,
        ]);
        $token = $user->createToken('iphone')->plainTextToken;

        $this->withHeader('X-Request-Id', 'suspended-user')
            ->withToken($token)
            ->getJson('/api/v1/me')
            ->assertForbidden()
            ->assertHeader('X-Request-Id', 'suspended-user')
            ->assertJsonPath('code', 'forbidden')
            ->assertJsonPath('message', 'You do not have permission to perform this action.');
    }
}
