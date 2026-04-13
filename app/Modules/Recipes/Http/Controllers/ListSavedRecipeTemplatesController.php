<?php

namespace App\Modules\Recipes\Http\Controllers;

use App\Modules\Recipes\Application\Services\RecipeTemplateInteractionService;
use App\Modules\Recipes\Http\Requests\ListRecipeTemplateInteractionsRequest;
use App\Modules\Recipes\Http\Resources\RecipeTemplateInteractionResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ListSavedRecipeTemplatesController
{
    public function __invoke(
        ListRecipeTemplateInteractionsRequest $request,
        RecipeTemplateInteractionService $recipeTemplateInteractionService
    ): AnonymousResourceCollection {
        return RecipeTemplateInteractionResource::collection(
            $recipeTemplateInteractionService->paginateSavedSuggestionsForUser(
                $request->user(),
                $request->perPage()
            )
        );
    }
}
