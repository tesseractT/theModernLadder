<?php

namespace App\Modules\Ingredients\Http\Controllers;

use App\Modules\Ingredients\Domain\Models\Ingredient;
use App\Modules\Ingredients\Http\Resources\IngredientResource;
use App\Modules\Shared\Http\Controllers\ApiController;
use Illuminate\Http\Resources\Json\JsonResource;

class ShowIngredientController extends ApiController
{
    public function __invoke(string $slug): JsonResource
    {
        $ingredient = Ingredient::query()
            ->published()
            ->with('aliases')
            ->where('slug', $slug)
            ->firstOrFail();

        return IngredientResource::make($ingredient);
    }
}
