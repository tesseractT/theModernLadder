<?php

namespace App\Modules\Pantry\Http\Controllers;

use App\Modules\Pantry\Application\Services\PantryItemService;
use App\Modules\Pantry\Domain\Models\PantryItem;
use App\Modules\Pantry\Http\Requests\UpdatePantryItemRequest;
use App\Modules\Pantry\Http\Resources\PantryItemResource;
use App\Modules\Shared\Http\Controllers\ApiController;
use Illuminate\Http\JsonResponse;

class UpdatePantryItemController extends ApiController
{
    public function __invoke(
        UpdatePantryItemRequest $request,
        PantryItemService $pantryItemService,
        PantryItem $pantryItem
    ): JsonResponse {
        $this->authorize('update', $pantryItem);

        $updatedPantryItem = $pantryItemService->update(
            $pantryItem,
            $request->payload()
        );

        return $this->respond([
            'message' => 'Pantry item updated successfully.',
            'pantry_item' => PantryItemResource::make($updatedPantryItem)->resolve($request),
        ]);
    }
}
