<?php

namespace App\Modules\Recipes\Http\Controllers;

use App\Modules\Recipes\Application\Services\RecipeTemplateInteractionService;
use App\Modules\Shared\Http\Controllers\ApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RemoveSavedRecipeTemplateController extends ApiController
{
    public function __invoke(
        Request $request,
        string $recipeTemplate,
        RecipeTemplateInteractionService $recipeTemplateInteractionService
    ): JsonResponse {
        $recipeTemplateInteractionService->removeSavedSuggestion($request->user(), $recipeTemplate);

        return $this->respond([
            'message' => 'Saved suggestion removed successfully.',
        ]);
    }
}
