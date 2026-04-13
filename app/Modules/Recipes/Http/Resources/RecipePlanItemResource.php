<?php

namespace App\Modules\Recipes\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RecipePlanItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'horizon' => $this->horizon?->value,
            'note' => $this->note,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'template' => $this->recipeTemplate
                ? RecipeTemplateSummaryResource::make($this->recipeTemplate)->resolve($request)
                : null,
        ];
    }
}
