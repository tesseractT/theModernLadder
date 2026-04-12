<?php

namespace App\Modules\Auth\Http\Controllers;

use App\Modules\Auth\Http\Requests\LoginRequest;
use App\Modules\Shared\Application\Services\SecurityAuditLogger;
use App\Modules\Shared\Http\Controllers\ApiController;
use App\Modules\Users\Application\Services\AccountService;
use App\Modules\Users\Http\Resources\UserAccountResource;
use Illuminate\Http\JsonResponse;

class LoginController extends ApiController
{
    public function __invoke(
        LoginRequest $request,
        AccountService $accountService,
        SecurityAuditLogger $securityAuditLogger,
    ): JsonResponse {
        $user = $request->authenticate();

        $user->forceFill([
            'last_seen_at' => now(),
        ])->save();

        $user = $accountService->load($user);
        $token = $user->createToken($request->deviceName());

        $securityAuditLogger->log(
            event: 'auth.login.succeeded',
            request: $request,
            actorId: (string) $user->getKey(),
            context: [
                'target_type' => 'personal_access_token',
                'target_id' => (string) $token->accessToken->getKey(),
            ],
        );

        return $this->respond([
            'message' => 'Login completed successfully.',
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'user' => UserAccountResource::make($user)->resolve($request),
        ]);
    }
}
