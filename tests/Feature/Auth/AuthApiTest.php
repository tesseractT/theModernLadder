<?php

namespace Tests\Feature\Auth;

use App\Modules\Users\Domain\Enums\UserRole;
use App\Modules\Users\Domain\Models\User;
use App\Modules\Users\Domain\Models\UserPreference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Testing\TestResponse;
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

        $this->assertArrayNotHasKey('role', $response->json('user'));

        $this->assertDatabaseHas('profiles', [
            'user_id' => $user->id,
            'display_name' => 'Casey Morgan',
        ]);

        $this->assertDatabaseHas('user_preferences', [
            'user_id' => $user->id,
            'key' => UserPreference::FOOD_PREFERENCES_KEY,
        ]);

        $this->assertSame(UserRole::User, $user->role);

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

    public function test_register_response_disables_client_side_caching_and_emits_a_security_audit_log(): void
    {
        $requestId = 'register-security-req';

        Log::spy();

        $response = $this->withHeaders([
            'X-Request-Id' => $requestId,
        ])->postJson('/api/v1/auth/register', [
            'name' => 'Casey Morgan',
            'email' => 'casey@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'device_name' => 'iphone-15',
        ]);

        $response
            ->assertCreated()
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Referrer-Policy', 'no-referrer');

        $plainTextToken = (string) $response->json('token');
        $user = User::query()->where('email', 'casey@example.com')->firstOrFail();

        Log::shouldHaveReceived('info')
            ->withArgs(function (string $message, array $context) use ($requestId, $plainTextToken, $user): bool {
                return $message === 'security.audit'
                    && ($context['event'] ?? null) === 'auth.register.succeeded'
                    && ($context['actor_id'] ?? null) === (string) $user->id
                    && ($context['request_id'] ?? null) === $requestId
                    && ($context['target_type'] ?? null) === 'personal_access_token'
                    && filled($context['target_id'] ?? null)
                    && ! str_contains(json_encode($context, JSON_THROW_ON_ERROR), $plainTextToken);
            })
            ->once();
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

    public function test_logout_all_revokes_every_active_token_for_the_current_user(): void
    {
        $user = User::factory()->create();
        $firstToken = $user->createToken('iphone')->plainTextToken;
        $secondToken = $user->createToken('ipad')->plainTextToken;

        $this->withToken($firstToken)
            ->postJson('/api/v1/auth/logout/all')
            ->assertOk()
            ->assertJsonPath('message', 'All active tokens revoked successfully.');

        $this->assertDatabaseCount('personal_access_tokens', 0);
        app('auth')->forgetGuards();

        $this->withToken($firstToken)
            ->getJson('/api/v1/me')
            ->assertUnauthorized()
            ->assertJsonPath('code', 'unauthenticated');

        app('auth')->forgetGuards();

        $this->withToken($secondToken)
            ->getJson('/api/v1/me')
            ->assertUnauthorized()
            ->assertJsonPath('code', 'unauthenticated');
    }

    public function test_logout_all_emits_a_security_audit_log_without_exposing_any_bearer_token(): void
    {
        $requestId = 'logout-all-req-123';
        $user = User::factory()->create();
        $firstToken = $user->createToken('iphone')->plainTextToken;
        $secondToken = $user->createToken('ipad')->plainTextToken;

        Log::spy();

        $this->withHeaders([
            'X-Request-Id' => $requestId,
        ])->withToken($firstToken)
            ->postJson('/api/v1/auth/logout/all')
            ->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private');

        Log::shouldHaveReceived('info')
            ->withArgs(function (string $message, array $context) use ($firstToken, $secondToken, $requestId, $user): bool {
                return $message === 'security.audit'
                    && ($context['event'] ?? null) === 'auth.logout_all.succeeded'
                    && ($context['actor_id'] ?? null) === (string) $user->id
                    && ($context['request_id'] ?? null) === $requestId
                    && ($context['target_type'] ?? null) === 'user'
                    && ($context['target_id'] ?? null) === (string) $user->id
                    && ($context['revoked_token_count'] ?? null) === 2
                    && ! str_contains(json_encode($context, JSON_THROW_ON_ERROR), $firstToken)
                    && ! str_contains(json_encode($context, JSON_THROW_ON_ERROR), $secondToken);
            })
            ->once();
    }

    public function test_register_route_is_throttled_with_a_safe_json_response(): void
    {
        config()->set('api.route_rate_limits.auth.register.per_minute', 1);

        $this->postJson('/api/v1/auth/register', [
            'name' => 'Casey Morgan',
            'email' => 'casey@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'device_name' => 'iphone-15',
        ])->assertCreated();

        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Jamie Morgan',
            'email' => 'jamie@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'device_name' => 'iphone-15',
        ]);

        $this->assertTooManyRequestsResponse($response);
    }

    public function test_login_route_is_throttled_with_a_safe_json_response(): void
    {
        config()->set('api.route_rate_limits.auth.login.per_minute', 1);

        User::factory()->create([
            'email' => 'jamie@example.com',
            'password' => 'Password123!',
        ]);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'jamie@example.com',
            'password' => 'Password123!',
            'device_name' => 'pixel-9',
        ])->assertOk();

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'jamie@example.com',
            'password' => 'Password123!',
            'device_name' => 'pixel-9',
        ]);

        $this->assertTooManyRequestsResponse($response);
        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    public function test_login_failed_credential_lockout_remains_explicit_and_validation_shaped(): void
    {
        config()->set('api.route_rate_limits.auth.login.per_minute', 10);
        config()->set('api.route_rate_limits.auth.login.credential_lockout.max_attempts', 2);
        config()->set('api.route_rate_limits.auth.login.credential_lockout.decay_seconds', 60);

        User::factory()->create([
            'email' => 'jamie@example.com',
            'password' => 'Password123!',
        ]);

        $payload = [
            'email' => 'jamie@example.com',
            'password' => 'wrong-password',
            'device_name' => 'pixel-9',
        ];

        $this->postJson('/api/v1/auth/login', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');

        $this->postJson('/api/v1/auth/login', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');

        $response = $this->postJson('/api/v1/auth/login', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');

        $this->assertStringContainsString(
            'Too many',
            (string) $response->json('errors.email.0')
        );
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_logout_route_is_throttled_with_a_safe_json_response(): void
    {
        config()->set('api.route_rate_limits.auth.logout.per_minute', 1);

        $user = User::factory()->create();
        $firstToken = $user->createToken('iphone')->plainTextToken;
        $secondToken = $user->createToken('ipad')->plainTextToken;

        $this->withToken($firstToken)
            ->postJson('/api/v1/auth/logout')
            ->assertOk();

        $response = $this->withToken($secondToken)
            ->postJson('/api/v1/auth/logout');

        $this->assertTooManyRequestsResponse($response);
        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    protected function assertTooManyRequestsResponse(TestResponse $response): void
    {
        $response
            ->assertStatus(429)
            ->assertHeader('Retry-After')
            ->assertHeader('X-Request-Id')
            ->assertJsonPath('code', 'too_many_requests')
            ->assertJsonPath('message', 'Too many requests. Please try again later.');

        $this->assertGreaterThan(0, (int) $response->json('retry_after_seconds'));
    }

    public function test_authenticated_api_responses_disable_client_side_caching(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('iphone')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/v1/me')
            ->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private');
    }
}
