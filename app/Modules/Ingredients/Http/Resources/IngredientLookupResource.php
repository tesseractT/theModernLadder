<?php

namespace App\Modules\Ingredients\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IngredientLookupResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'matched_alias' => $this->whenLoaded(
                'aliases',
                fn () => $this->aliases->first()?->alias
            ),
        ];
    }
}
