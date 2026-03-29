<?php

namespace App\Modules\Recipes\Http\Controllers;

use App\Modules\Recipes\Domain\Models\RecipeTemplate;
use App\Modules\Recipes\Http\Resources\RecipeTemplateResource;
use App\Modules\Shared\Http\Controllers\ApiController;
use Illuminate\Http\Resources\Json\JsonResource;

class ShowRecipeTemplateController extends ApiController
{
    public function __invoke(string $slug): JsonResource
    {
        $recipeTemplate = RecipeTemplate::query()
            ->published()
            ->where('slug', $slug)
            ->firstOrFail();

        return RecipeTemplateResource::make($recipeTemplate);
    }
}
