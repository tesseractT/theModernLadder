<?php

namespace App\Modules\Users\Http\Controllers;

use App\Modules\Shared\Http\Controllers\ApiController;
use App\Modules\Users\Application\Services\AccountService;
use App\Modules\Users\Http\Resources\UserAccountResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShowCurrentUserController extends ApiController
{
    public function __invoke(Request $request, AccountService $accountService): JsonResponse
    {
        $this->authorize('view', $request->user());

        $user = $accountService->load($request->user());

        return $this->respond([
            'user' => UserAccountResource::make($user)->resolve($request),
        ]);
    }
}
