<?php

namespace App\Modules\Ingredients\Http\Controllers;

use App\Modules\Ingredients\Domain\Models\Ingredient;
use App\Modules\Ingredients\Http\Requests\SearchIngredientsRequest;
use App\Modules\Ingredients\Http\Resources\IngredientLookupResource;
use App\Modules\Shared\Http\Controllers\ApiController;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;

class SearchIngredientsController extends ApiController
{
    public function __invoke(SearchIngredientsRequest $request): JsonResponse
    {
        $term = $request->queryTerm();
        $like = '%'.$term.'%';

        $ingredients = Ingredient::query()
            ->published()
            ->with([
                'aliases' => function ($query) use ($like): void {
                    $query
                        ->where(function (Builder $aliasQuery) use ($like): void {
                            $aliasQuery
                                ->whereRaw('LOWER(alias) LIKE ?', [$like])
                                ->orWhere('normalized_alias', 'like', $like);
                        })
                        ->orderBy('alias');
                },
            ])
            ->where(function (Builder $query) use ($like): void {
                $query
                    ->whereRaw('LOWER(name) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(slug) LIKE ?', [$like])
                    ->orWhereHas('aliases', function (Builder $aliasQuery) use ($like): void {
                        $aliasQuery
                            ->whereRaw('LOWER(alias) LIKE ?', [$like])
                            ->orWhere('normalized_alias', 'like', $like);
                    });
            })
            ->orderByRaw(
                'CASE WHEN LOWER(name) LIKE ? THEN 0 WHEN LOWER(slug) LIKE ? THEN 1 ELSE 2 END',
                [$term.'%', $term.'%']
            )
            ->orderBy('name')
            ->limit($request->resultLimit())
            ->get();

        return $this->respond([
            'data' => IngredientLookupResource::collection($ingredients)->resolve($request),
        ]);
    }
}
