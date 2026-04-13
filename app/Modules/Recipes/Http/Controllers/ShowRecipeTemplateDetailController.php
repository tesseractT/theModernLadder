<?php

namespace App\Modules\Recipes\Http\Controllers;

use App\Modules\Recipes\Application\Services\RecipeTemplateDetailService;
use App\Modules\Recipes\Application\Services\RecipeTemplateInteractionService;
use App\Modules\Recipes\Domain\Enums\RecipeTemplateInteractionSource;
use App\Modules\Recipes\Http\Resources\RecipeTemplateDetailResource;
use App\Modules\Shared\Http\Controllers\ApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShowRecipeTemplateDetailController extends ApiController
{
    public function __invoke(
        Request $request,
        string $recipeTemplate,
        RecipeTemplateDetailService $recipeTemplateDetailService,
        RecipeTemplateInteractionService $recipeTemplateInteractionService,
    ): JsonResponse {
        $detail = $recipeTemplateDetailService->detailForUser(
            $request->user(),
            $recipeTemplate
        );

        $recipeTemplateInteractionService->recordRecentHistory(
            $request->user(),
            $recipeTemplate,
            RecipeTemplateInteractionSource::RecipeDetail,
        );

        return $this->respond(
            RecipeTemplateDetailResource::make($detail)->resolve($request)
        );
    }
}
