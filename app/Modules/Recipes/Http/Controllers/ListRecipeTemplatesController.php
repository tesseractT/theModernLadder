<?php

namespace App\Modules\Recipes\Http\Controllers;

use App\Modules\Recipes\Domain\Models\RecipeTemplate;
use App\Modules\Recipes\Http\Requests\ListRecipeTemplatesRequest;
use App\Modules\Recipes\Http\Resources\RecipeTemplateResource;
use App\Modules\Shared\Http\Controllers\ApiController;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ListRecipeTemplatesController extends ApiController
{
    public function __invoke(ListRecipeTemplatesRequest $request): AnonymousResourceCollection
    {
        $search = $request->search();

        $recipeTemplates = RecipeTemplate::query()
            ->published()
            ->when($search, function (Builder $query) use ($search): void {
                $like = '%'.$search.'%';

                $query->where(function (Builder $recipeQuery) use ($like): void {
                    $recipeQuery
                        ->where('title', 'like', $like)
                        ->orWhere('summary', 'like', $like);
                });
            })
            ->orderBy('title')
            ->paginate($request->perPage())
            ->withQueryString();

        return RecipeTemplateResource::collection($recipeTemplates);
    }
}
