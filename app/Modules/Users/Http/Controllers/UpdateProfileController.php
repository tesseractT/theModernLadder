<?php

namespace App\Modules\Users\Http\Controllers;

use App\Modules\Shared\Http\Controllers\ApiController;
use App\Modules\Users\Application\Services\AccountService;
use App\Modules\Users\Http\Requests\UpdateProfileRequest;
use App\Modules\Users\Http\Resources\UserAccountResource;
use Illuminate\Http\JsonResponse;

class UpdateProfileController extends ApiController
{
    public function __invoke(UpdateProfileRequest $request, AccountService $accountService): JsonResponse
    {
        $user = $accountService->updateProfile(
            $request->user(),
            $request->profileAttributes()
        );

        return $this->respond([
            'message' => 'Profile updated successfully.',
            'user' => UserAccountResource::make($user)->resolve($request),
        ]);
    }
}
