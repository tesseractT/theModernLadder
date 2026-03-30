<?php

namespace App\Modules\Pantry\Http\Controllers;

use App\Modules\Ingredients\Domain\Models\Ingredient;
use App\Modules\Pantry\Application\Services\PantryItemService;
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
        $ingredient = Ingredient::query()
            ->published()
            ->findOrFail($request->string('ingredient_id')->toString());

        $pantryItem = $pantryItemService->createForUser(
            $request->user(),
            $ingredient,
            $request->pantryAttributes()
        );

        return $this->respond([
            'message' => 'Pantry item added successfully.',
            'pantry_item' => PantryItemResource::make($pantryItem)->resolve($request),
        ], 201);
    }
}
