<?php

namespace App\Modules\Pantry\Http\Controllers;

use App\Modules\Pantry\Application\Services\PantryItemService;
use App\Modules\Shared\Http\Controllers\ApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeletePantryItemController extends ApiController
{
    public function __invoke(
        Request $request,
        PantryItemService $pantryItemService,
        string $pantryItem
    ): JsonResponse {
        $pantryItemService->deleteForUser($request->user(), $pantryItem);

        return $this->respond([
            'message' => 'Pantry item removed successfully.',
        ]);
    }
}
