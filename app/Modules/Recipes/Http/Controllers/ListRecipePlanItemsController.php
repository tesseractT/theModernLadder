<?php

namespace App\Modules\Recipes\Http\Controllers;

use App\Modules\Recipes\Application\Services\RecipePlanService;
use App\Modules\Recipes\Http\Requests\ListRecipePlanItemsRequest;
use App\Modules\Recipes\Http\Resources\RecipePlanItemResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ListRecipePlanItemsController
{
    public function __invoke(
        ListRecipePlanItemsRequest $request,
        RecipePlanService $recipePlanService
    ): AnonymousResourceCollection {
        return RecipePlanItemResource::collection(
            $recipePlanService->paginateForUser(
                $request->user(),
                $request->perPage(),
                $request->horizon(),
            )
        );
    }
}
