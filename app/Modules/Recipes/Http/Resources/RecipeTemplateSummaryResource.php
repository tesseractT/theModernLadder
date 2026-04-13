<?php

namespace App\Modules\Recipes\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RecipeTemplateSummaryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $timingValues = collect([$this->prep_minutes, $this->cook_minutes])
            ->filter(fn ($value) => $value !== null);

        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'recipe_type' => $this->recipe_type?->value,
            'difficulty' => $this->difficulty?->value,
            'dietary_patterns' => $this->dietary_patterns ?? [],
            'summary' => $this->summary,
            'servings' => $this->servings,
            'prep_minutes' => $this->prep_minutes,
            'cook_minutes' => $this->cook_minutes,
            'total_minutes' => $timingValues->isEmpty() ? null : (int) $timingValues->sum(),
        ];
    }
}
