<?php

namespace App\Modules\Users\Http\Controllers;

use App\Modules\Shared\Http\Controllers\ApiController;
use App\Modules\Users\Application\Services\AccountService;
use App\Modules\Users\Http\Requests\UpdatePreferencesRequest;
use App\Modules\Users\Http\Resources\UserAccountResource;
use Illuminate\Http\JsonResponse;

class UpdatePreferencesController extends ApiController
{
    public function __invoke(UpdatePreferencesRequest $request, AccountService $accountService): JsonResponse
    {
        $user = $accountService->updateFoodPreferences(
            $request->user(),
            $request->preferenceAttributes()
        );

        return $this->respond([
            'message' => 'Preferences updated successfully.',
            'user' => UserAccountResource::make($user)->resolve($request),
        ]);
    }
}
