<?php

namespace App\Modules\Recipes\Http\Controllers;

use App\Modules\Pantry\Http\Resources\PantryItemResource;
use App\Modules\Recipes\Application\Services\RecipeSuggestionService;
use App\Modules\Recipes\Http\Requests\GenerateSuggestionsRequest;
use App\Modules\Recipes\Http\Resources\SuggestionCandidateResource;
use App\Modules\Shared\Http\Controllers\ApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;

class GenerateSuggestionsController extends ApiController
{
    public function __invoke(
        GenerateSuggestionsRequest $request,
        RecipeSuggestionService $recipeSuggestionService
    ): JsonResponse {
        $result = $recipeSuggestionService->generateForUser(
            $request->user(),
            $request->filters()
        );

        $payload = [
            'request' => $result['request'],
            'pantry' => [
                'count' => $result['pantry_items']->count(),
                'items' => PantryItemResource::collection($result['pantry_items'])->resolve($request),
            ],
            'candidates' => SuggestionCandidateResource::collection(
                new Collection($result['candidates'])
            )->resolve($request),
            'meta' => [
                'count' => count($result['candidates']),
            ],
        ];

        if (is_string($result['message']) && $result['message'] !== '') {
            $payload['message'] = $result['message'];
        }

        return $this->respond($payload);
    }
}
