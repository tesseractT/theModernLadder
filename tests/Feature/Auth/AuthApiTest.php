<?php

namespace Tests\Feature\Auth;

use App\Modules\Users\Domain\Models\User;
use App\Modules\Users\Domain\Models\UserPreference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_and_receives_a_token_with_account_payload(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Casey Morgan',
            'email' => 'casey@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'device_name' => 'iphone-15',
        ]);

        $user = User::query()->where('email', 'casey@example.com')->firstOrFail();

        $response
            ->assertCreated()
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonPath('user.email', 'casey@example.com')
            ->assertJsonPath('user.profile.display_name', 'Casey Morgan')
            ->assertJsonPath('user.preferences.measurement_system', 'metric');

        $this->assertDatabaseHas('profiles', [
            'user_id' => $user->id,
            'display_name' => 'Casey Morgan',
        ]);

        $this->assertDatabaseHas('user_preferences', [
            'user_id' => $user->id,
            'key' => UserPreference::FOOD_PREFERENCES_KEY,
        ]);

        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    public function test_user_can_log_in_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'jamie@example.com',
            'password' => 'Password123!',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'jamie@example.com',
            'password' => 'Password123!',
            'device_name' => 'pixel-9',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonPath('user.email', 'jamie@example.com')
            ->assertJsonPath('user.profile.display_name', 'Jamie')
            ->assertJsonPath('user.preferences.measurement_system', 'metric');

        $this->assertDatabaseHas('profiles', [
            'user_id' => $user->id,
            'display_name' => 'Jamie',
        ]);

        $this->assertDatabaseHas('user_preferences', [
            'user_id' => $user->id,
            'key' => UserPreference::FOOD_PREFERENCES_KEY,
        ]);

        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    public function test_login_fails_with_invalid_credentials(): void
    {
        User::factory()->create([
            'email' => 'jamie@example.com',
            'password' => 'Password123!',
        ]);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'jamie@example.com',
            'password' => 'wrong-password',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');
    }

    public function test_logout_revokes_the_current_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('iphone')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/v1/auth/logout')
            ->assertOk()
            ->assertJsonPath('message', 'Logout completed successfully.');

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }
}
