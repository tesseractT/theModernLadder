<?php

namespace App\Modules\Recipes\Http\Controllers;

use App\Modules\Recipes\Application\Services\RecipeSuggestionService;
use App\Modules\Recipes\Http\Requests\GenerateSuggestionsRequest;
use App\Modules\Recipes\Http\Resources\SuggestionResponseResource;
use App\Modules\Shared\Http\Controllers\ApiController;
use Illuminate\Http\JsonResponse;

class GenerateSuggestionsController extends ApiController
{
    public function __invoke(
        GenerateSuggestionsRequest $request,
        RecipeSuggestionService $recipeSuggestionService
    ): JsonResponse {
        $result = $recipeSuggestionService->generateForUser(
            $request->user(),
            $request->payload()
        );

        return $this->respond(
            SuggestionResponseResource::make($result)->resolve($request)
        );
    }
}
