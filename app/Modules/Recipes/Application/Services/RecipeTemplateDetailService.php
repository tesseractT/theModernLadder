<?php

namespace App\Modules\Recipes\Application\Services;

use App\Modules\Ingredients\Domain\Models\Ingredient;
use App\Modules\Ingredients\Domain\Models\Substitution;
use App\Modules\Pantry\Domain\Models\PantryItem;
use App\Modules\Recipes\Domain\Models\RecipeTemplate;
use App\Modules\Recipes\Domain\Models\RecipeTemplateStep;
use App\Modules\Users\Domain\Models\User;
use Illuminate\Support\Collection;

class RecipeTemplateDetailService
{
    public function detailForUser(User $user, string $recipeTemplateId): array
    {
        $recipeTemplate = RecipeTemplate::query()
            ->published()
            ->with([
                'templateIngredients' => function ($query): void {
                    $query
                        ->orderBy('sort_order')
                        ->orderBy('created_at')
                        ->with('ingredient');
                },
                'steps' => function ($query): void {
                    $query
                        ->orderBy('position')
                        ->orderBy('created_at');
                },
            ])
            ->findOrFail($recipeTemplateId);

        $pantryItems = PantryItem::query()
            ->ownedBy($user)
            ->active()
            ->with('ingredient')
            ->orderByDesc('updated_at')
            ->get()
            ->filter(fn (PantryItem $pantryItem) => $pantryItem->ingredient !== null)
            ->values();

        $pantryItemsByIngredientId = $pantryItems->keyBy('ingredient_id');
        $pantryIngredientIds = $pantryItemsByIngredientId->keys()->values();
        $templateIngredientIds = $recipeTemplate->templateIngredients
            ->pluck('ingredient_id')
            ->filter()
            ->unique()
            ->values();

        $substitutionsByIngredientId = $this->availableSubstitutions(
            $templateIngredientIds,
            $pantryIngredientIds
        );

        [$requiredIngredients, $requiredSummary, $requiredSubstitutions] = $this->ingredientGroupPayload(
            $recipeTemplate->templateIngredients->where('is_required', true)->values(),
            $pantryItemsByIngredientId,
            $substitutionsByIngredientId
        );
        [$optionalIngredients, $optionalSummary, $optionalSubstitutions] = $this->ingredientGroupPayload(
            $recipeTemplate->templateIngredients->where('is_required', false)->values(),
            $pantryItemsByIngredientId,
            $substitutionsByIngredientId
        );

        $missingRequiredCount = $requiredSummary['missing_count'];
        $coveredRequiredMissingCount = $requiredSummary['covered_missing_count'];

        return [
            'template' => [
                'id' => $recipeTemplate->id,
                'slug' => $recipeTemplate->slug,
                'title' => $recipeTemplate->title,
                'recipe_type' => $recipeTemplate->recipe_type?->value,
                'difficulty' => $recipeTemplate->difficulty?->value,
                'summary' => $recipeTemplate->summary,
                'dietary_patterns' => $recipeTemplate->dietary_patterns ?? [],
                'servings' => $recipeTemplate->servings,
                'prep_minutes' => $recipeTemplate->prep_minutes,
                'cook_minutes' => $recipeTemplate->cook_minutes,
                'total_minutes' => $this->totalMinutes(
                    $recipeTemplate->prep_minutes,
                    $recipeTemplate->cook_minutes
                ),
            ],
            'pantry_fit' => [
                'required_total' => $requiredSummary['total'],
                'required_owned' => $requiredSummary['owned_count'],
                'required_missing' => $requiredSummary['missing_count'],
                'optional_total' => $optionalSummary['total'],
                'optional_owned' => $optionalSummary['owned_count'],
                'optional_missing' => $optionalSummary['missing_count'],
                'substitution_covered_required_missing' => $coveredRequiredMissingCount,
                'can_make_with_current_pantry' => $missingRequiredCount === 0,
                'can_make_after_substitutions' => $missingRequiredCount === 0
                    || $missingRequiredCount === $coveredRequiredMissingCount,
            ],
            'ingredients' => [
                'required' => $requiredIngredients,
                'optional' => $optionalIngredients,
            ],
            'steps' => $this->stepPayloads($recipeTemplate),
            'substitutions' => [
                ...$requiredSubstitutions,
                ...$optionalSubstitutions,
            ],
        ];
    }

    protected function ingredientGroupPayload(
        Collection $templateIngredients,
        Collection $pantryItemsByIngredientId,
        Collection $substitutionsByIngredientId
    ): array {
        $ingredients = [];
        $substitutions = [];
        $ownedCount = 0;
        $missingCount = 0;
        $coveredMissingCount = 0;

        foreach ($templateIngredients as $templateIngredient) {
            /** @var Ingredient|null $ingredient */
            $ingredient = $templateIngredient->ingredient;

            if (! $ingredient) {
                continue;
            }

            $pantryItem = $pantryItemsByIngredientId->get($ingredient->id);
            $availableSubstitutes = collect($substitutionsByIngredientId->get($ingredient->id, []))
                ->map(function (Substitution $substitution) use ($pantryItemsByIngredientId): ?array {
                    $pantryItem = $pantryItemsByIngredientId->get($substitution->substitute_ingredient_id);

                    if (! $pantryItem || ! $pantryItem->ingredient) {
                        return null;
                    }

                    return [
                        'pantry_item_id' => $pantryItem->id,
                        'ingredient' => $this->ingredientPayload($pantryItem->ingredient),
                        'note' => $substitution->note,
                    ];
                })
                ->filter()
                ->sortBy(fn (array $substitute) => $substitute['ingredient']['name'])
                ->values()
                ->all();

            $isOwned = $pantryItem !== null;

            if ($isOwned) {
                $ownedCount++;
            } else {
                $missingCount++;
            }

            if (! $isOwned && $availableSubstitutes !== []) {
                $coveredMissingCount++;

                $substitutions[] = [
                    'for_ingredient' => $this->ingredientPayload($ingredient),
                    'available_substitutes' => $availableSubstitutes,
                ];
            }

            $ingredients[] = [
                'position' => $templateIngredient->sort_order,
                'ingredient' => $this->ingredientPayload($ingredient),
                'is_required' => $templateIngredient->is_required,
                'is_owned' => $isOwned,
                'pantry_item_id' => $pantryItem?->id,
                'substitutions' => $availableSubstitutes,
            ];
        }

        return [
            $ingredients,
            [
                'total' => count($ingredients),
                'owned_count' => $ownedCount,
                'missing_count' => $missingCount,
                'covered_missing_count' => $coveredMissingCount,
            ],
            $substitutions,
        ];
    }

    protected function availableSubstitutions(
        Collection $templateIngredientIds,
        Collection $pantryIngredientIds
    ): Collection {
        if ($templateIngredientIds->isEmpty() || $pantryIngredientIds->isEmpty()) {
            return collect();
        }

        return Substitution::query()
            ->published()
            ->with('substituteIngredient')
            ->whereIn('ingredient_id', $templateIngredientIds->all())
            ->whereIn('substitute_ingredient_id', $pantryIngredientIds->all())
            ->orderBy('ingredient_id')
            ->get()
            ->groupBy('ingredient_id');
    }

    protected function stepPayloads(RecipeTemplate $recipeTemplate): array
    {
        $steps = $recipeTemplate->steps->isNotEmpty()
            ? $recipeTemplate->steps->map(
                fn (RecipeTemplateStep $step) => [
                    'position' => $step->position,
                    'instruction' => $step->instruction,
                ]
            )->values()
            : $this->fallbackSteps($recipeTemplate->instructions);

        return $steps->all();
    }

    protected function fallbackSteps(?string $instructions): Collection
    {
        if (! is_string($instructions) || trim($instructions) === '') {
            return collect();
        }

        $rawSteps = preg_split("/(?:\r\n|\r|\n){2,}/", trim($instructions))
            ?: preg_split("/(?:\r\n|\r|\n)/", trim($instructions))
            ?: [];

        return collect($rawSteps)
            ->map(fn ($instruction) => trim((string) $instruction))
            ->filter()
            ->values()
            ->map(fn (string $instruction, int $index) => [
                'position' => $index + 1,
                'instruction' => $instruction,
            ]);
    }

    protected function ingredientPayload(Ingredient $ingredient): array
    {
        return [
            'id' => $ingredient->id,
            'name' => $ingredient->name,
            'slug' => $ingredient->slug,
            'description' => $ingredient->description,
        ];
    }

    protected function totalMinutes(?int $prepMinutes, ?int $cookMinutes): ?int
    {
        $values = collect([$prepMinutes, $cookMinutes])
            ->filter(fn ($value) => $value !== null);

        return $values->isEmpty() ? null : (int) $values->sum();
    }
}
