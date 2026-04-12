<?php

namespace App\Modules\Pantry\Http\Controllers;

use App\Modules\Pantry\Application\Services\PantryItemService;
use App\Modules\Pantry\Domain\Models\PantryItem;
use App\Modules\Pantry\Http\Requests\StorePantryItemRequest;
use App\Modules\Pantry\Http\Resources\PantryItemResource;
use App\Modules\Shared\Http\Controllers\ApiController;
use Illuminate\Http\JsonResponse;

class StorePantryItemController extends ApiController
{
    public function __invoke(
        StorePantryItemRequest $request,
        PantryItemService $pantryItemService
    ): JsonResponse {
        $this->authorize('create', PantryItem::class);

        $pantryItem = $pantryItemService->createForUser(
            $request->user(),
            $request->payload()
        );

        return $this->respond([
            'message' => 'Pantry item added successfully.',
            'pantry_item' => PantryItemResource::make($pantryItem)->resolve($request),
        ], 201);
    }
}
