<?php

namespace App\Modules\Pantry\Http\Resources;

use App\Modules\Ingredients\Http\Resources\IngredientLookupResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PantryItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ingredient' => $this->ingredient
                ? IngredientLookupResource::make($this->ingredient)->resolve($request)
                : null,
            'entered_name' => $this->entered_name,
            'quantity' => $this->quantity !== null ? round((float) $this->quantity, 2) : null,
            'unit' => $this->unit,
            'note' => $this->note,
            'expires_on' => $this->expires_on?->toDateString(),
            'status' => $this->status?->value,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
