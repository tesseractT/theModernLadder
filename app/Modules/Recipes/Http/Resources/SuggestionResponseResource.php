<?php

namespace App\Modules\Recipes\Http\Resources;

use App\Modules\Pantry\Http\Resources\PantryItemResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

class SuggestionResponseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $payload = [
            'request' => $this->input->toResponseArray(),
            'pantry' => [
                'count' => $this->pantryItems->count(),
                'items' => PantryItemResource::collection($this->pantryItems)->resolve($request),
            ],
            'candidates' => SuggestionCandidateResource::collection(
                new Collection($this->candidates)
            )->resolve($request),
            'meta' => [
                'count' => count($this->candidates),
            ],
        ];

        if (is_string($this->message) && $this->message !== '') {
            $payload['message'] = $this->message;
        }

        return $payload;
    }
}
