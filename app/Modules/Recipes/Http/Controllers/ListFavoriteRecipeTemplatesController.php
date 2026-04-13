<?php

namespace App\Modules\Recipes\Http\Controllers;

use App\Modules\Recipes\Application\Services\RecipeTemplateInteractionService;
use App\Modules\Recipes\Http\Requests\ListRecipeTemplateInteractionsRequest;
use App\Modules\Recipes\Http\Resources\RecipeTemplateInteractionResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ListFavoriteRecipeTemplatesController
{
    public function __invoke(
        ListRecipeTemplateInteractionsRequest $request,
        RecipeTemplateInteractionService $recipeTemplateInteractionService
    ): AnonymousResourceCollection {
        return RecipeTemplateInteractionResource::collection(
            $recipeTemplateInteractionService->paginateFavoritesForUser(
                $request->user(),
                $request->perPage()
            )
        );
    }
}
