<?php

namespace App\Modules\Recipes\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RecipeTemplateInteractionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'interaction_type' => $this->interaction_type?->value,
            'source' => $this->source?->value,
            'goal' => $this->goal,
            'interacted_at' => $this->interacted_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'template' => $this->recipeTemplate
                ? RecipeTemplateSummaryResource::make($this->recipeTemplate)->resolve($request)
                : null,
        ];
    }
}
