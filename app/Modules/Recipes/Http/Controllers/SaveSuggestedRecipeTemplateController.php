<?php

namespace App\Modules\Recipes\Http\Controllers;

use App\Modules\Recipes\Application\Services\RecipeTemplateInteractionService;
use App\Modules\Recipes\Http\Requests\SaveRecipeTemplateSuggestionRequest;
use App\Modules\Recipes\Http\Resources\RecipeTemplateInteractionResource;
use App\Modules\Shared\Http\Controllers\ApiController;
use Illuminate\Http\JsonResponse;

class SaveSuggestedRecipeTemplateController extends ApiController
{
    public function __invoke(
        SaveRecipeTemplateSuggestionRequest $request,
        string $recipeTemplate,
        RecipeTemplateInteractionService $recipeTemplateInteractionService
    ): JsonResponse {
        $interaction = $recipeTemplateInteractionService->saveSuggestionForUser(
            $request->user(),
            $recipeTemplate,
            $request->payload()
        );

        return $this->respond(
            RecipeTemplateInteractionResource::make($interaction)->resolve($request)
        );
    }
}
