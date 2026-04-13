<?php

namespace App\Modules\Recipes\Http\Controllers;

use App\Modules\Recipes\Application\Services\RecipePlanService;
use App\Modules\Recipes\Domain\Models\RecipePlanItem;
use App\Modules\Recipes\Http\Requests\UpdateRecipePlanItemRequest;
use App\Modules\Recipes\Http\Resources\RecipePlanItemResource;
use App\Modules\Shared\Http\Controllers\ApiController;
use Illuminate\Http\JsonResponse;

class UpdateRecipePlanItemController extends ApiController
{
    public function __invoke(
        UpdateRecipePlanItemRequest $request,
        RecipePlanItem $recipePlanItem,
        RecipePlanService $recipePlanService
    ): JsonResponse {
        $updated = $recipePlanService->update($recipePlanItem, $request->payload());

        return $this->respond(
            RecipePlanItemResource::make($updated)->resolve($request)
        );
    }
}
