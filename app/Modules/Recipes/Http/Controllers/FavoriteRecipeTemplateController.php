<?php

namespace App\Modules\Recipes\Http\Controllers;

use App\Modules\Recipes\Application\Services\RecipeTemplateInteractionService;
use App\Modules\Recipes\Http\Requests\FavoriteRecipeTemplateRequest;
use App\Modules\Recipes\Http\Resources\RecipeTemplateInteractionResource;
use App\Modules\Shared\Http\Controllers\ApiController;
use Illuminate\Http\JsonResponse;

class FavoriteRecipeTemplateController extends ApiController
{
    public function __invoke(
        FavoriteRecipeTemplateRequest $request,
        string $recipeTemplate,
        RecipeTemplateInteractionService $recipeTemplateInteractionService
    ): JsonResponse {
        $interaction = $recipeTemplateInteractionService->favorite(
            $request->user(),
            $recipeTemplate
        );

        return $this->respond(
            RecipeTemplateInteractionResource::make($interaction)->resolve($request)
        );
    }
}
