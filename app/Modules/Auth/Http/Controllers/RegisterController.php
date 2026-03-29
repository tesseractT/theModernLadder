<?php

namespace App\Modules\Auth\Http\Controllers;

use App\Modules\Auth\Application\Actions\RegisterUser;
use App\Modules\Auth\Http\Requests\RegisterRequest;
use App\Modules\Shared\Http\Controllers\ApiController;
use App\Modules\Users\Http\Resources\UserAccountResource;
use Illuminate\Http\JsonResponse;

class RegisterController extends ApiController
{
    public function __invoke(RegisterRequest $request, RegisterUser $registerUser): JsonResponse
    {
        $user = $registerUser->execute($request->validated());
        $token = $user->createToken($request->deviceName());

        return $this->respond([
            'message' => 'Registration completed successfully.',
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'user' => UserAccountResource::make($user)->resolve($request),
        ], 201);
    }
}
