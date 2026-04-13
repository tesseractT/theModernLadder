<?php

namespace App\Modules\Moderation\Http\Resources;

use App\Modules\Contributions\Http\Resources\ContributionResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ModerationContributionDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'contribution' => ContributionResource::make($this->resource)->resolve($request),
            'moderation_cases' => $this->relationLoaded('moderationCases')
                ? ModerationCaseResource::collection($this->moderationCases)->resolve($request)
                : [],
            'moderation_history' => $this->relationLoaded('moderationActions')
                ? ModerationActionResource::collection($this->moderationActions)->resolve($request)
                : [],
        ];
    }
}
