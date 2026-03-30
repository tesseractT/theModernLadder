<?php

namespace App\Modules\Recipes\Http\Controllers;

use App\Modules\Recipes\Application\Services\RecipeTemplateDetailService;
use App\Modules\Recipes\Http\Resources\RecipeTemplateDetailResource;
use App\Modules\Shared\Http\Controllers\ApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShowRecipeTemplateDetailController extends ApiController
{
    public function __invoke(
        Request $request,
        string $recipeTemplate,
        RecipeTemplateDetailService $recipeTemplateDetailService
    ): JsonResponse {
        $detail = $recipeTemplateDetailService->detailForUser(
            $request->user(),
            $recipeTemplate
        );

        return $this->respond(
            RecipeTemplateDetailResource::make($detail)->resolve($request)
        );
    }
}
