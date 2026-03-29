<?php

namespace App\Modules\Auth\Http\Controllers;

use App\Modules\Auth\Http\Requests\LoginRequest;
use App\Modules\Shared\Http\Controllers\ApiController;
use App\Modules\Users\Application\Services\AccountService;
use App\Modules\Users\Http\Resources\UserAccountResource;
use Illuminate\Http\JsonResponse;

class LoginController extends ApiController
{
    public function __invoke(LoginRequest $request, AccountService $accountService): JsonResponse
    {
        $user = $request->authenticate();

        $user->forceFill([
            'last_seen_at' => now(),
        ])->save();

        $user = $accountService->load($user);
        $token = $user->createToken($request->deviceName());

        return $this->respond([
            'message' => 'Login completed successfully.',
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'user' => UserAccountResource::make($user)->resolve($request),
        ]);
    }
}
