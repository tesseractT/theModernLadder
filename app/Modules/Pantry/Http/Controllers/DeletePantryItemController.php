<?php

namespace App\Modules\Pantry\Http\Controllers;

use App\Modules\Pantry\Application\Services\PantryItemService;
use App\Modules\Pantry\Domain\Models\PantryItem;
use App\Modules\Shared\Http\Controllers\ApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeletePantryItemController extends ApiController
{
    public function __invoke(
        Request $request,
        PantryItemService $pantryItemService,
        PantryItem $pantryItem
    ): JsonResponse {
        $this->authorize('delete', $pantryItem);

        $pantryItemService->delete($pantryItem);

        return $this->respond([
            'message' => 'Pantry item removed successfully.',
        ]);
    }
}
