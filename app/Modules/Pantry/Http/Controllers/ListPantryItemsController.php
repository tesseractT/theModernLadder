<?php

namespace App\Modules\Pantry\Http\Controllers;

use App\Modules\Pantry\Application\Services\PantryItemService;
use App\Modules\Pantry\Http\Requests\ListPantryItemsRequest;
use App\Modules\Pantry\Http\Resources\PantryItemResource;
use App\Modules\Shared\Http\Controllers\ApiController;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ListPantryItemsController extends ApiController
{
    public function __invoke(
        ListPantryItemsRequest $request,
        PantryItemService $pantryItemService
    ): AnonymousResourceCollection {
        $pantryItems = $pantryItemService->paginateForUser(
            $request->user(),
            $request->perPage()
        );

        return PantryItemResource::collection($pantryItems);
    }
}
