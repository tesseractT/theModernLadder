<?php

namespace App\Modules\Recipes\Application\Services;

use App\Modules\Ingredients\Domain\Models\Ingredient;
use App\Modules\Ingredients\Domain\Models\Pairing;
use App\Modules\Ingredients\Domain\Models\Substitution;
use App\Modules\Pantry\Domain\Models\PantryItem;
use App\Modules\Recipes\Domain\Models\RecipeTemplate;
use App\Modules\Recipes\Domain\Models\RecipeTemplateIngredient;
use App\Modules\Users\Domain\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class RecipeSuggestionService
{
    public function generateForUser(User $user, array $filters): array
    {
        $goal = $filters['goal'] ?? null;
        $includeSubstitutions = (bool) ($filters['include_substitutions']
            ?? config('suggestions.defaults.include_substitutions', true));
        $limit = (int) ($filters['limit'] ?? config('suggestions.defaults.limit', 5));
        $selectedPantryItemIds = collect($filters['pantry_item_ids'] ?? [])
            ->filter()
            ->values();

        $pantryItems = PantryItem::query()
            ->ownedBy($user)
            ->active()
            ->with('ingredient')
            ->when(
                $selectedPantryItemIds->isNotEmpty(),
                fn ($query) => $query->whereIn('id', $selectedPantryItemIds->all())
            )
            ->orderByDesc('updated_at')
            ->get()
            ->filter(fn (PantryItem $pantryItem) => $pantryItem->ingredient !== null)
            ->values();

        $response = [
            'request' => [
                'goal' => $goal,
                'limit' => $limit,
                'include_substitutions' => $includeSubstitutions,
                'pantry_item_ids' => $selectedPantryItemIds->all(),
            ],
            'pantry_items' => $pantryItems,
            'candidates' => [],
            'message' => null,
        ];

        if ($pantryItems->isEmpty()) {
            $response['message'] = 'Add pantry ingredients to get suggestion candidates.';

            return $response;
        }

        $pantryIngredientIds = $pantryItems
            ->pluck('ingredient_id')
            ->filter()
            ->unique()
            ->values();

        $pantryItemsByIngredientId = $pantryItems->keyBy('ingredient_id');
        $preferences = $this->normalizedPreferences($user);

        $templates = RecipeTemplate::query()
            ->published()
            ->when($goal, fn ($query) => $query->where('recipe_type', $goal))
            ->whereHas('templateIngredients', fn ($query) => $query->where('is_required', true))
            ->with([
                'templateIngredients' => function ($query): void {
                    $query
                        ->orderBy('sort_order')
                        ->orderBy('created_at')
                        ->with(['ingredient.aliases']);
                },
            ])
            ->get()
            ->filter(fn (RecipeTemplate $template) => $template->templateIngredients->isNotEmpty())
            ->values();

        if ($templates->isEmpty()) {
            $response['message'] = 'No suggestion templates are currently available for this pantry selection.';

            return $response;
        }

        $templateIngredientIds = $templates
            ->flatMap(fn (RecipeTemplate $template) => $template->templateIngredients->pluck('ingredient_id'))
            ->filter()
            ->unique()
            ->values();

        $substitutionsByIngredientId = $includeSubstitutions
            ? $this->availableSubstitutions($templateIngredientIds, $pantryIngredientIds)
            : collect();

        $pairingMap = $this->pairingMap($templateIngredientIds, $pantryIngredientIds);

        $candidates = $templates
            ->map(function (
                RecipeTemplate $template
            ) use (
                $goal,
                $includeSubstitutions,
                $pairingMap,
                $pantryIngredientIds,
                $pantryItemsByIngredientId,
                $preferences,
                $substitutionsByIngredientId
            ): ?array {
                return $this->buildCandidate(
                    $template,
                    $pantryIngredientIds,
                    $pantryItemsByIngredientId,
                    $preferences,
                    $substitutionsByIngredientId,
                    $pairingMap,
                    $goal,
                    $includeSubstitutions
                );
            })
            ->filter()
            ->sort(function (array $left, array $right): int {
                return ($right['score'] <=> $left['score'])
                    ?: ($right['match_summary']['required_matched'] <=> $left['match_summary']['required_matched'])
                    ?: ($left['match_summary']['missing_required_count'] <=> $right['match_summary']['missing_required_count'])
                    ?: strcmp($left['title'], $right['title']);
            })
            ->take($limit)
            ->values()
            ->all();

        $response['candidates'] = $candidates;

        if ($candidates === []) {
            $response['message'] = 'No suggestion candidates matched the selected pantry ingredients and preferences.';
        }

        return $response;
    }

    protected function buildCandidate(
        RecipeTemplate $template,
        Collection $pantryIngredientIds,
        Collection $pantryItemsByIngredientId,
        array $preferences,
        Collection $substitutionsByIngredientId,
        array $pairingMap,
        ?string $goal,
        bool $includeSubstitutions
    ): ?array {
        $requiredIngredients = $template->templateIngredients
            ->where('is_required', true)
            ->values();
        $optionalIngredients = $template->templateIngredients
            ->where('is_required', false)
            ->values();

        if ($requiredIngredients->isEmpty()) {
            return null;
        }

        $templatePatterns = collect($template->dietary_patterns ?? [])
            ->map(fn ($pattern) => Str::lower(trim((string) $pattern)))
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (! $this->supportsDietaryPatterns(
            $templatePatterns,
            $preferences['dietary_patterns']
        )) {
            return null;
        }

        if ($this->hasDislikedRequiredIngredient(
            $requiredIngredients,
            $preferences['disliked_ingredients']
        )) {
            return null;
        }

        $matchedRequired = $requiredIngredients
            ->filter(fn (RecipeTemplateIngredient $item) => $pantryIngredientIds->contains($item->ingredient_id))
            ->values();
        $matchedOptional = $optionalIngredients
            ->filter(fn (RecipeTemplateIngredient $item) => $pantryIngredientIds->contains($item->ingredient_id))
            ->values();

        if ($matchedRequired->isEmpty() && $matchedOptional->isEmpty()) {
            return null;
        }

        $missingRequired = $requiredIngredients
            ->reject(fn (RecipeTemplateIngredient $item) => $pantryIngredientIds->contains($item->ingredient_id))
            ->values();

        [$missingIngredients, $substitutions, $coveredMissingCount] = $includeSubstitutions
            ? $this->resolveMissingIngredients(
                $missingRequired,
                $substitutionsByIngredientId,
                $pantryItemsByIngredientId
            )
            : [
                $this->missingIngredientPayloads($missingRequired),
                [],
                0,
            ];

        $pairingSignals = $this->pairingSignals(
            $missingRequired,
            $pantryItemsByIngredientId,
            $pairingMap
        );

        $scoreBreakdown = $this->scoreBreakdown(
            $matchedRequired->count(),
            $matchedOptional->count(),
            $missingRequired->count(),
            $coveredMissingCount,
            count($pairingSignals),
            $goal !== null && $template->recipe_type?->value === $goal
        );

        $score = array_sum($scoreBreakdown);

        if ($score <= 0) {
            return null;
        }

        $reasonCodes = collect([
            $matchedRequired->isNotEmpty() ? 'matched_required_ingredients' : null,
            $missingRequired->isEmpty() ? 'all_required_available' : null,
            $matchedOptional->isNotEmpty() ? 'matched_optional_ingredients' : null,
            $coveredMissingCount > 0 ? 'substitution_available' : null,
            $pairingSignals !== [] ? 'pairing_supported' : null,
            $goal !== null && $template->recipe_type?->value === $goal ? 'goal_matched' : null,
            $preferences['dietary_patterns'] !== [] ? 'dietary_patterns_respected' : null,
        ])->filter()->values()->all();

        return [
            'id' => 'recipe_template:'.$template->id,
            'source' => [
                'type' => 'recipe_template',
                'id' => $template->id,
                'slug' => $template->slug,
            ],
            'recipe_template_id' => $template->id,
            'suggestion_type' => $template->recipe_type?->value ?? 'general',
            'title' => $template->title,
            'summary' => $template->summary,
            'score' => $score,
            'score_breakdown' => $scoreBreakdown,
            'reason_codes' => $reasonCodes,
            'matched_ingredients' => [
                ...$this->matchedIngredientPayloads($matchedRequired, $pantryItemsByIngredientId),
                ...$this->matchedIngredientPayloads($matchedOptional, $pantryItemsByIngredientId),
            ],
            'missing_ingredients' => $missingIngredients,
            'substitutions' => $substitutions,
            'pairing_signals' => $pairingSignals,
            'preference_compatibility' => [
                'is_compatible' => true,
                'dietary_patterns_applied' => $preferences['dietary_patterns'],
                'template_dietary_patterns' => $templatePatterns,
                'disliked_ingredients_checked' => $preferences['disliked_ingredients'],
            ],
            'match_summary' => [
                'required_total' => $requiredIngredients->count(),
                'required_matched' => $matchedRequired->count(),
                'optional_total' => $optionalIngredients->count(),
                'optional_matched' => $matchedOptional->count(),
                'missing_required_count' => $missingRequired->count(),
                'substitution_covered_missing_count' => $coveredMissingCount,
                'pairing_signal_count' => count($pairingSignals),
            ],
        ];
    }

    protected function normalizedPreferences(User $user): array
    {
        $preferences = $user->resolvedFoodPreferences();

        return [
            'dietary_patterns' => collect($preferences['dietary_patterns'] ?? [])
                ->map(fn ($pattern) => Str::lower(trim((string) $pattern)))
                ->reject(fn (string $pattern) => $pattern === '' || $pattern === 'omnivore')
                ->unique()
                ->values()
                ->all(),
            'disliked_ingredients' => collect($preferences['disliked_ingredients'] ?? [])
                ->map(fn ($ingredient) => Str::lower(trim((string) $ingredient)))
                ->filter()
                ->unique()
                ->values()
                ->all(),
        ];
    }

    protected function supportsDietaryPatterns(
        array $templatePatterns,
        array $requiredPatterns
    ): bool {
        if ($requiredPatterns === []) {
            return true;
        }

        if ($templatePatterns === []) {
            return false;
        }

        return collect($requiredPatterns)
            ->every(fn (string $pattern) => in_array($pattern, $templatePatterns, true));
    }

    protected function hasDislikedRequiredIngredient(
        Collection $requiredIngredients,
        array $dislikedIngredients
    ): bool {
        if ($dislikedIngredients === []) {
            return false;
        }

        $dislikedLookup = collect($dislikedIngredients)
            ->map(fn (string $value) => Str::lower($value))
            ->flip();

        return $requiredIngredients->contains(function (RecipeTemplateIngredient $templateIngredient) use ($dislikedLookup): bool {
            /** @var Ingredient|null $ingredient */
            $ingredient = $templateIngredient->ingredient;

            if (! $ingredient) {
                return false;
            }

            $matches = collect([
                Str::lower($ingredient->name),
                Str::lower($ingredient->slug),
                ...$ingredient->aliases->map(fn ($alias) => Str::lower($alias->alias))->all(),
                ...$ingredient->aliases->map(fn ($alias) => Str::lower($alias->normalized_alias))->all(),
            ]);

            return $matches->contains(fn (string $value) => $dislikedLookup->has($value));
        });
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

    protected function pairingMap(
        Collection $templateIngredientIds,
        Collection $pantryIngredientIds
    ): array {
        if ($templateIngredientIds->isEmpty() || $pantryIngredientIds->isEmpty()) {
            return [];
        }

        $pairings = Pairing::query()
            ->published()
            ->where(function ($query) use ($templateIngredientIds, $pantryIngredientIds): void {
                $query
                    ->whereIn('ingredient_id', $templateIngredientIds->all())
                    ->whereIn('paired_ingredient_id', $pantryIngredientIds->all())
                    ->orWhere(function ($inverseQuery) use ($templateIngredientIds, $pantryIngredientIds): void {
                        $inverseQuery
                            ->whereIn('ingredient_id', $pantryIngredientIds->all())
                            ->whereIn('paired_ingredient_id', $templateIngredientIds->all());
                    });
            })
            ->get();

        $pairingMap = [];

        foreach ($pairings as $pairing) {
            $payload = [
                'strength' => $pairing->strength,
                'note' => $pairing->note,
            ];

            $pairingMap[$pairing->ingredient_id.':'.$pairing->paired_ingredient_id] = $payload;
            $pairingMap[$pairing->paired_ingredient_id.':'.$pairing->ingredient_id] = $payload;
        }

        return $pairingMap;
    }

    protected function resolveMissingIngredients(
        Collection $missingRequired,
        Collection $substitutionsByIngredientId,
        Collection $pantryItemsByIngredientId
    ): array {
        $missingIngredients = [];
        $substitutions = [];
        $coveredMissingCount = 0;

        foreach ($missingRequired as $templateIngredient) {
            /** @var Ingredient|null $ingredient */
            $ingredient = $templateIngredient->ingredient;

            if (! $ingredient) {
                continue;
            }

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

            if ($availableSubstitutes !== []) {
                $coveredMissingCount++;

                $substitutions[] = [
                    'missing_ingredient' => $this->ingredientPayload($ingredient),
                    'available_substitutes' => $availableSubstitutes,
                ];
            }

            $missingIngredients[] = [
                'ingredient' => $this->ingredientPayload($ingredient),
                'is_required' => true,
                'covered_by_substitution' => $availableSubstitutes !== [],
            ];
        }

        return [$missingIngredients, $substitutions, $coveredMissingCount];
    }

    protected function missingIngredientPayloads(Collection $missingRequired): array
    {
        return $missingRequired
            ->map(function (RecipeTemplateIngredient $templateIngredient): ?array {
                if (! $templateIngredient->ingredient) {
                    return null;
                }

                return [
                    'ingredient' => $this->ingredientPayload($templateIngredient->ingredient),
                    'is_required' => true,
                    'covered_by_substitution' => false,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    protected function matchedIngredientPayloads(
        Collection $templateIngredients,
        Collection $pantryItemsByIngredientId
    ): array {
        return $templateIngredients
            ->map(function (RecipeTemplateIngredient $templateIngredient) use ($pantryItemsByIngredientId): ?array {
                $pantryItem = $pantryItemsByIngredientId->get($templateIngredient->ingredient_id);

                if (! $pantryItem || ! $templateIngredient->ingredient) {
                    return null;
                }

                return [
                    'pantry_item_id' => $pantryItem->id,
                    'ingredient' => $this->ingredientPayload($templateIngredient->ingredient),
                    'is_required' => $templateIngredient->is_required,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    protected function pairingSignals(
        Collection $missingRequired,
        Collection $pantryItemsByIngredientId,
        array $pairingMap
    ): array {
        if ($missingRequired->isEmpty() || $pairingMap === []) {
            return [];
        }

        $signals = [];
        $pantryItems = $pantryItemsByIngredientId->sortBy(
            fn (PantryItem $pantryItem) => $pantryItem->ingredient?->name ?? $pantryItem->entered_name
        );
        $maxSignals = (int) config('suggestions.limits.max_pairing_signals_per_candidate', 3);

        foreach ($missingRequired as $templateIngredient) {
            /** @var Ingredient|null $missingIngredient */
            $missingIngredient = $templateIngredient->ingredient;

            if (! $missingIngredient) {
                continue;
            }

            foreach ($pantryItems as $pantryItem) {
                if (! $pantryItem->ingredient) {
                    continue;
                }

                $pairing = $pairingMap[$missingIngredient->id.':'.$pantryItem->ingredient_id] ?? null;

                if (! is_array($pairing)) {
                    continue;
                }

                $signals[] = [
                    'pantry_ingredient' => $this->ingredientPayload($pantryItem->ingredient),
                    'target_ingredient' => $this->ingredientPayload($missingIngredient),
                    'strength' => $pairing['strength'],
                    'note' => $pairing['note'],
                ];

                break;
            }

            if (count($signals) >= $maxSignals) {
                break;
            }
        }

        return $signals;
    }

    protected function scoreBreakdown(
        int $matchedRequiredCount,
        int $matchedOptionalCount,
        int $missingRequiredCount,
        int $coveredMissingCount,
        int $pairingSignalCount,
        bool $goalMatched
    ): array {
        $weights = config('suggestions.scoring', []);
        $uncoveredMissingCount = max(0, $missingRequiredCount - $coveredMissingCount);

        return [
            'required_match' => $matchedRequiredCount * (int) ($weights['required_match'] ?? 40),
            'optional_match' => $matchedOptionalCount * (int) ($weights['optional_match'] ?? 8),
            'perfect_required_match' => $missingRequiredCount === 0
                ? (int) ($weights['perfect_required_match'] ?? 14)
                : 0,
            'goal_match' => $goalMatched
                ? (int) ($weights['goal_match'] ?? 10)
                : 0,
            'substitution_coverage' => $coveredMissingCount * (int) ($weights['substitution_coverage'] ?? 8),
            'pairing_signal' => $pairingSignalCount * (int) ($weights['pairing_signal'] ?? 3),
            'missing_without_substitution_penalty' => $uncoveredMissingCount
                * -1
                * (int) ($weights['missing_without_substitution_penalty'] ?? 18),
        ];
    }

    protected function ingredientPayload(Ingredient $ingredient): array
    {
        return [
            'id' => $ingredient->id,
            'name' => $ingredient->name,
            'slug' => $ingredient->slug,
        ];
    }
}
