<?php

namespace App\Modules\Recipes\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RecipeTemplateDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'template' => $this['template'],
            'pantry_fit' => $this['pantry_fit'],
            'ingredients' => $this['ingredients'],
            'steps' => $this['steps'],
            'substitutions' => $this['substitutions'],
        ];
    }
}
