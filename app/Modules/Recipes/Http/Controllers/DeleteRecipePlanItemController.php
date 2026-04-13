<?php

namespace App\Modules\Recipes\Http\Controllers;

use App\Modules\Recipes\Application\Services\RecipePlanService;
use App\Modules\Recipes\Domain\Models\RecipePlanItem;
use App\Modules\Shared\Http\Controllers\ApiController;
use Illuminate\Http\JsonResponse;

class DeleteRecipePlanItemController extends ApiController
{
    public function __invoke(
        RecipePlanItem $recipePlanItem,
        RecipePlanService $recipePlanService
    ): JsonResponse {
        $recipePlanService->delete($recipePlanItem);

        return $this->respond([
            'message' => 'Plan item removed successfully.',
        ]);
    }
}
