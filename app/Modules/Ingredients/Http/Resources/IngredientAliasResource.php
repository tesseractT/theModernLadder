<?php

namespace App\Modules\Ingredients\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IngredientAliasResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'alias' => $this->alias,
            'normalized_alias' => $this->normalized_alias,
            'locale' => $this->locale,
        ];
    }
}
