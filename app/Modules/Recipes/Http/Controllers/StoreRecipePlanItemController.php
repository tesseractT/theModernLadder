<?php

namespace App\Modules\Recipes\Http\Controllers;

use App\Modules\Recipes\Application\Services\RecipePlanService;
use App\Modules\Recipes\Http\Requests\StoreRecipePlanItemRequest;
use App\Modules\Recipes\Http\Resources\RecipePlanItemResource;
use App\Modules\Shared\Http\Controllers\ApiController;
use Illuminate\Http\JsonResponse;

class StoreRecipePlanItemController extends ApiController
{
    public function __invoke(
        StoreRecipePlanItemRequest $request,
        RecipePlanService $recipePlanService
    ): JsonResponse {
        $recipePlanItem = $recipePlanService->createForUser(
            $request->user(),
            $request->payload()
        );

        return $this->respond(
            RecipePlanItemResource::make($recipePlanItem)->resolve($request),
            $recipePlanItem->wasRecentlyCreated ? 201 : 200
        );
    }
}
