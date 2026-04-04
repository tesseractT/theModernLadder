<?php

namespace App\Modules\AI\Application\DTO;

use App\Modules\Users\Domain\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

final readonly class RecipeExplanationContext
{
    public function __construct(
        public string $requestId,
        public string $userId,
        public string $templateId,
        public array $template,
        public array $pantryFit,
        public array $ingredients,
        public array $steps,
        public array $substitutions,
        public array $preferences,
        public array $allowedFollowUpOptions,
        public string $promptVersion,
        public string $schemaVersion,
    ) {}

    public static function fromDetail(
        User $user,
        array $detail,
        string $requestId,
        string $promptVersion,
        string $schemaVersion,
    ): self {
        $template = Arr::get($detail, 'template', []);
        $pantryFit = Arr::get($detail, 'pantry_fit', []);
        $ingredients = Arr::get($detail, 'ingredients', []);
        $steps = Arr::get($detail, 'steps', []);
        $substitutions = Arr::get($detail, 'substitutions', []);

        $preferences = self::safePreferences($user);

        return new self(
            requestId: $requestId,
            userId: (string) $user->getKey(),
            templateId: (string) ($template['id'] ?? ''),
            template: $template,
            pantryFit: $pantryFit,
            ingredients: $ingredients,
            steps: $steps,
            substitutions: $substitutions,
            preferences: $preferences,
            allowedFollowUpOptions: self::allowedFollowUpOptions($template, $pantryFit, $ingredients, $substitutions),
            promptVersion: $promptVersion,
            schemaVersion: $schemaVersion,
        );
    }

    public function promptPayload(): array
    {
        return [
            'prompt_version' => $this->promptVersion,
            'schema_version' => $this->schemaVersion,
            'template' => [
                'id' => $this->template['id'] ?? null,
                'slug' => $this->template['slug'] ?? null,
                'title' => $this->template['title'] ?? null,
                'recipe_type' => $this->template['recipe_type'] ?? null,
                'difficulty' => $this->template['difficulty'] ?? null,
                'summary' => $this->template['summary'] ?? null,
                'dietary_patterns' => $this->template['dietary_patterns'] ?? [],
                'servings' => $this->template['servings'] ?? null,
                'prep_minutes' => $this->template['prep_minutes'] ?? null,
                'cook_minutes' => $this->template['cook_minutes'] ?? null,
                'total_minutes' => $this->template['total_minutes'] ?? null,
            ],
            'pantry_fit' => $this->pantryFit,
            'ingredients' => [
                'required' => $this->ingredientGroupForPrompt($this->ingredients['required'] ?? []),
                'optional' => $this->ingredientGroupForPrompt($this->ingredients['optional'] ?? []),
            ],
            'steps' => collect($this->steps)
                ->map(fn (array $step) => [
                    'position' => $step['position'] ?? null,
                    'instruction' => $step['instruction'] ?? null,
                ])
                ->values()
                ->all(),
            'substitutions' => collect($this->substitutions)
                ->map(function (array $substitution): array {
                    return [
                        'for_ingredient' => [
                            'name' => Arr::get($substitution, 'for_ingredient.name'),
                            'slug' => Arr::get($substitution, 'for_ingredient.slug'),
                            'description' => Arr::get($substitution, 'for_ingredient.description'),
                        ],
                        'available_substitutes' => collect($substitution['available_substitutes'] ?? [])
                            ->map(fn (array $substitute) => [
                                'ingredient' => [
                                    'name' => Arr::get($substitute, 'ingredient.name'),
                                    'slug' => Arr::get($substitute, 'ingredient.slug'),
                                    'description' => Arr::get($substitute, 'ingredient.description'),
                                ],
                                'note' => $substitute['note'] ?? null,
                            ])
                            ->values()
                            ->all(),
                    ];
                })
                ->values()
                ->all(),
            'preferences' => $this->preferences,
            'allowed_follow_up_options' => $this->allowedFollowUpOptions,
        ];
    }

    public function grounding(): array
    {
        return [
            'template' => [
                'id' => $this->template['id'] ?? null,
                'slug' => $this->template['slug'] ?? null,
                'title' => $this->template['title'] ?? null,
                'recipe_type' => $this->template['recipe_type'] ?? null,
                'difficulty' => $this->template['difficulty'] ?? null,
                'servings' => $this->template['servings'] ?? null,
                'total_minutes' => $this->template['total_minutes'] ?? null,
            ],
            'pantry_fit' => $this->pantryFit,
            'owned_ingredients' => $this->flatIngredientGroup()
                ->where('is_owned', true)
                ->map(fn (array $ingredient) => Arr::only($ingredient['ingredient'] ?? [], ['name', 'slug']))
                ->values()
                ->all(),
            'missing_ingredients' => $this->flatIngredientGroup()
                ->where('is_owned', false)
                ->map(fn (array $ingredient) => Arr::only($ingredient['ingredient'] ?? [], ['name', 'slug']))
                ->values()
                ->all(),
            'available_substitutions' => collect($this->substitutions)
                ->map(fn (array $substitution) => [
                    'for_ingredient' => Arr::only($substitution['for_ingredient'] ?? [], ['name', 'slug']),
                    'available_substitutes' => collect($substitution['available_substitutes'] ?? [])
                        ->map(fn (array $substitute) => Arr::only($substitute['ingredient'] ?? [], ['name', 'slug']))
                        ->values()
                        ->all(),
                ])
                ->values()
                ->all(),
            'dietary_patterns_considered' => [
                'user' => $this->preferences['dietary_patterns'] ?? [],
                'template' => $this->template['dietary_patterns'] ?? [],
            ],
        ];
    }

    public function allowedFollowUpKeys(): array
    {
        return collect($this->allowedFollowUpOptions)
            ->pluck('key')
            ->filter()
            ->values()
            ->all();
    }

    protected function ingredientGroupForPrompt(array $ingredients): array
    {
        return collect($ingredients)
            ->map(function (array $item): array {
                return [
                    'position' => $item['position'] ?? null,
                    'ingredient' => Arr::only($item['ingredient'] ?? [], ['name', 'slug', 'description']),
                    'is_required' => (bool) ($item['is_required'] ?? false),
                    'is_owned' => (bool) ($item['is_owned'] ?? false),
                    'substitutions' => collect($item['substitutions'] ?? [])
                        ->map(fn (array $substitute) => [
                            'ingredient' => Arr::only($substitute['ingredient'] ?? [], ['name', 'slug', 'description']),
                            'note' => $substitute['note'] ?? null,
                        ])
                        ->values()
                        ->all(),
                ];
            })
            ->values()
            ->all();
    }

    protected function flatIngredientGroup(): Collection
    {
        return collect([
            ...($this->ingredients['required'] ?? []),
            ...($this->ingredients['optional'] ?? []),
        ])->values();
    }

    protected static function safePreferences(User $user): array
    {
        $preferences = $user->resolvedFoodPreferences();

        return [
            'dietary_patterns' => collect($preferences['dietary_patterns'] ?? [])
                ->map(fn ($pattern) => Str::lower(trim((string) $pattern)))
                ->filter()
                ->intersect(config('user_preferences.allowed.dietary_patterns', []))
                ->values()
                ->all(),
        ];
    }

    protected static function allowedFollowUpOptions(
        array $template,
        array $pantryFit,
        array $ingredients,
        array $substitutions,
    ): array {
        $options = collect();

        if ((int) ($pantryFit['required_missing'] ?? 0) > 0) {
            $options->push([
                'key' => 'pantry_ready',
                'label_hint' => 'Want a version that works with what you already have?',
            ]);
        }

        if ($substitutions !== []) {
            $options->push([
                'key' => 'swap_help',
                'label_hint' => 'Need a pantry-based swap option?',
            ]);
        }

        $ingredientCount = count($ingredients['required'] ?? []) + count($ingredients['optional'] ?? []);

        if ($ingredientCount > 4) {
            $options->push([
                'key' => 'fewer_ingredients',
                'label_hint' => 'Want a version with fewer ingredients?',
            ]);
        }

        if ((int) ($template['total_minutes'] ?? 0) > 15) {
            $options->push([
                'key' => 'faster_version',
                'label_hint' => 'Want a faster version?',
            ]);
        }

        if (($template['recipe_type'] ?? null) !== 'drink') {
            $options->push([
                'key' => 'drink_version',
                'label_hint' => 'Want a drink version instead?',
            ]);
        }

        $options->push([
            'key' => 'same_pantry_new_recipe',
            'label_hint' => 'Want another idea using the same pantry?',
        ]);

        return $options
            ->unique('key')
            ->values()
            ->all();
    }
}
