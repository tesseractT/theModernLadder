<?php

namespace Tests\Feature\Auth;

use App\Modules\Users\Domain\Enums\UserRole;
use App\Modules\Users\Domain\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthorizationHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_role_helpers_and_gates_define_user_moderator_and_admin_boundaries(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::User,
        ]);
        $moderator = User::factory()->create([
            'role' => UserRole::Moderator,
        ]);
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
        ]);

        $this->assertTrue($user->isUser());
        $this->assertFalse($user->canModerate());
        $this->assertFalse(Gate::forUser($user)->allows('access-admin'));
        $this->assertFalse(Gate::forUser($user)->allows('access-moderation'));

        $this->assertTrue($moderator->isModerator());
        $this->assertTrue($moderator->canModerate());
        $this->assertFalse($moderator->isAdmin());
        $this->assertFalse(Gate::forUser($moderator)->allows('access-admin'));
        $this->assertTrue(Gate::forUser($moderator)->allows('access-moderation'));

        $this->assertTrue($admin->isAdmin());
        $this->assertTrue($admin->canModerate());
        $this->assertTrue(Gate::forUser($admin)->allows('access-admin'));
        $this->assertTrue(Gate::forUser($admin)->allows('access-moderation'));
    }

    public function test_privilege_escalation_attempts_fail_with_the_standardized_forbidden_response(): void
    {
        Route::middleware(['api', 'auth:sanctum', 'active.user', 'can:access-admin'])
            ->get('/api/v1/test/admin-boundary', fn () => response()->json(['ok' => true]));

        Sanctum::actingAs(User::factory()->create([
            'role' => UserRole::User,
        ]));

        $this->withHeader('X-Request-Id', 'admin-boundary-test')
            ->getJson('/api/v1/test/admin-boundary')
            ->assertForbidden()
            ->assertHeader('X-Request-Id', 'admin-boundary-test')
            ->assertJsonPath('code', 'forbidden')
            ->assertJsonPath('message', 'You do not have permission to perform this action.');
    }
}
