<?php

namespace App\Modules\Ingredients\Http\Controllers;

use App\Modules\Ingredients\Domain\Models\Ingredient;
use App\Modules\Ingredients\Http\Requests\ListIngredientsRequest;
use App\Modules\Ingredients\Http\Resources\IngredientResource;
use App\Modules\Shared\Http\Controllers\ApiController;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ListIngredientsController extends ApiController
{
    public function __invoke(ListIngredientsRequest $request): AnonymousResourceCollection
    {
        $search = $request->search();

        $ingredients = Ingredient::query()
            ->published()
            ->with('aliases')
            ->when($search, function (Builder $query) use ($search): void {
                $like = '%'.$search.'%';

                $query->where(function (Builder $ingredientQuery) use ($like): void {
                    $ingredientQuery
                        ->where('name', 'like', $like)
                        ->orWhere('slug', 'like', $like)
                        ->orWhereHas('aliases', function (Builder $aliasQuery) use ($like): void {
                            $aliasQuery
                                ->where('alias', 'like', $like)
                                ->orWhere('normalized_alias', 'like', $like);
                        });
                });
            })
            ->orderBy('name')
            ->paginate($request->perPage())
            ->withQueryString();

        return IngredientResource::collection($ingredients);
    }
}
