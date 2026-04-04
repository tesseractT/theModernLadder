<?php

namespace App\Modules\AI\Application\Support;

use App\Modules\AI\Application\DTO\RecipeExplanationContext;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class RecipeExplanationFallbackBuilder
{
    public function build(RecipeExplanationContext $context): array
    {
        $templateTitle = (string) ($context->template['title'] ?? 'This recipe');
        $ownedIngredients = Arr::pluck($context->grounding()['owned_ingredients'], 'name');
        $missingIngredients = Arr::pluck($context->grounding()['missing_ingredients'], 'name');
        $substitutions = collect($context->substitutions);

        $whyItFits = $context->pantryFit['can_make_with_current_pantry'] ?? false
            ? "{$templateTitle} fits because your pantry already covers the required ingredients."
            : $this->whyItFitsWithGaps($templateTitle, $ownedIngredients, $missingIngredients, $context);

        $tasteProfile = $this->tasteProfile($context, $ownedIngredients, $missingIngredients);
        $textureProfile = $this->textureProfile($context);
        $substitutionGuidance = $substitutions->map(function (array $substitution): string {
            $forIngredient = Arr::get($substitution, 'for_ingredient.name');
            $substituteNames = collect($substitution['available_substitutes'] ?? [])
                ->map(fn (array $substitute) => Arr::get($substitute, 'ingredient.name'))
                ->filter()
                ->values()
                ->all();

            if (! $forIngredient || $substituteNames === []) {
                return '';
            }

            return $forIngredient.' can be swapped with '.implode(', ', $substituteNames).'.';
        })->filter()->values()->take(3)->all();

        $quickTakeaways = array_values(array_filter([
            $this->requiredMatchTakeaway($context),
            $this->timeTakeaway($context),
            $substitutionGuidance !== [] ? 'Published pantry substitutions are available for at least one missing ingredient.' : null,
        ]));

        if (count($quickTakeaways) < 2) {
            $quickTakeaways[] = ($context->pantryFit['can_make_after_substitutions'] ?? false)
                ? 'The current pantry fit suggests you are close to making this template.'
                : 'The current pantry fit still has uncovered gaps.';
        }

        return [
            'headline' => $context->pantryFit['can_make_with_current_pantry'] ?? false
                ? $templateTitle.' is already within reach.'
                : $templateTitle.' is close with a few pantry-aware adjustments.',
            'why_it_fits' => $whyItFits,
            'taste_profile' => $tasteProfile,
            'texture_profile' => $textureProfile,
            'substitution_guidance' => $substitutionGuidance,
            'quick_takeaways' => $quickTakeaways,
            'follow_up_options' => collect($context->allowedFollowUpOptions)
                ->take(3)
                ->map(fn (array $option) => [
                    'key' => $option['key'],
                    'label' => $option['label_hint'],
                ])
                ->values()
                ->all(),
        ];
    }

    protected function whyItFitsWithGaps(
        string $templateTitle,
        array $ownedIngredients,
        array $missingIngredients,
        RecipeExplanationContext $context,
    ): string {
        $owned = $ownedIngredients !== []
            ? 'You already have '.implode(', ', array_slice($ownedIngredients, 0, 3)).'.'
            : 'You do not currently have the required ingredients in pantry.';
        $missing = $missingIngredients !== []
            ? 'You are still missing '.implode(', ', array_slice($missingIngredients, 0, 3)).'.'
            : null;
        $substitutions = ($context->pantryFit['substitution_covered_required_missing'] ?? 0) > 0
            ? 'The published substitution data covers part of that gap.'
            : null;

        return Str::of("{$templateTitle} still lines up well with your pantry. {$owned} {$missing} {$substitutions}")
            ->squish()
            ->toString();
    }

    protected function tasteProfile(
        RecipeExplanationContext $context,
        array $ownedIngredients,
        array $missingIngredients,
    ): string {
        $templateSummary = trim((string) ($context->template['summary'] ?? ''));

        if ($templateSummary !== '') {
            return 'Taste direction follows the template summary: '.$templateSummary;
        }

        $ingredients = array_filter([
            ...array_slice($ownedIngredients, 0, 2),
            ...array_slice($missingIngredients, 0, 1),
        ]);

        if ($ingredients === []) {
            return 'Taste can only be described in broad terms from the structured recipe data.';
        }

        return 'Taste is anchored by '.implode(', ', $ingredients).' from the recipe template.';
    }

    protected function textureProfile(RecipeExplanationContext $context): string
    {
        return match ($context->template['recipe_type'] ?? null) {
            'drink' => 'The texture is expected to stay drinkable and shaped by the listed base ingredients.',
            'light_meal' => 'Texture will come from the cut, mix, and serving style described in the template steps.',
            default => 'Texture follows the ingredients and steps already stored in the recipe template.',
        };
    }

    protected function requiredMatchTakeaway(RecipeExplanationContext $context): string
    {
        $owned = (int) ($context->pantryFit['required_owned'] ?? 0);
        $total = (int) ($context->pantryFit['required_total'] ?? 0);

        return "You already cover {$owned} of {$total} required ingredients.";
    }

    protected function timeTakeaway(RecipeExplanationContext $context): ?string
    {
        $totalMinutes = $context->template['total_minutes'] ?? null;

        if ($totalMinutes === null) {
            return null;
        }

        return "The structured template estimates about {$totalMinutes} total minutes.";
    }
}
