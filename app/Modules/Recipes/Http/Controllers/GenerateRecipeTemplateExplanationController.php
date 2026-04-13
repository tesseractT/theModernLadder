<?php

namespace App\Modules\Recipes\Http\Controllers;

use App\Modules\AI\Application\Exceptions\RecipeExplanationUnavailableException;
use App\Modules\AI\Application\Services\RecipeTemplateExplanationService;
use App\Modules\Recipes\Application\Services\RecipeTemplateInteractionService;
use App\Modules\Recipes\Domain\Enums\RecipeTemplateInteractionSource;
use App\Modules\Recipes\Http\Resources\RecipeTemplateExplanationResource;
use App\Modules\Shared\Http\Controllers\ApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GenerateRecipeTemplateExplanationController extends ApiController
{
    public function __invoke(
        Request $request,
        string $recipeTemplate,
        RecipeTemplateExplanationService $recipeTemplateExplanationService,
        RecipeTemplateInteractionService $recipeTemplateInteractionService,
    ): JsonResponse {
        try {
            $payload = $recipeTemplateExplanationService->generateForUser(
                user: $request->user(),
                recipeTemplateId: $recipeTemplate,
                requestId: (string) ($request->attributes->get('request_id') ?: $request->header('X-Request-Id')),
                routeName: $request->route()?->getName(),
            );
        } catch (RecipeExplanationUnavailableException) {
            return $this->respond([
                'message' => 'Unable to generate a recipe explanation right now.',
                'code' => 'recipe_explanation_unavailable',
            ], 503);
        }

        $recipeTemplateInteractionService->recordRecentHistory(
            $request->user(),
            $recipeTemplate,
            RecipeTemplateInteractionSource::RecipeExplanation,
        );

        return $this->respond(
            RecipeTemplateExplanationResource::make($payload)->resolve($request)
        );
    }
}
