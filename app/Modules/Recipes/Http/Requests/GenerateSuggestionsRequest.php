<?php

namespace App\Modules\Recipes\Http\Requests;

use App\Modules\Pantry\Domain\Models\PantryItem;
use App\Modules\Recipes\Application\DTO\GenerateSuggestionsData;
use App\Modules\Recipes\Domain\Enums\RecipeType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class GenerateSuggestionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $recipeType = $this->filled('recipe_type')
            ? Str::lower(trim((string) $this->input('recipe_type')))
            : null;

        $goal = $this->filled('goal')
            ? Str::lower(trim((string) $this->input('goal')))
            : null;

        if ($goal === null && $recipeType !== null) {
            $goal = $recipeType;
        }

        $this->merge([
            'goal' => $goal,
            'recipe_type' => $recipeType,
            'pantry_item_ids' => collect(Arr::wrap($this->input('pantry_item_ids')))
                ->map(fn ($value) => trim((string) $value))
                ->filter()
                ->unique()
                ->values()
                ->all(),
            'limit' => $this->integer('limit', (int) config('suggestions.defaults.limit', 5)),
            'include_substitutions' => $this->boolean(
                'include_substitutions',
                (bool) config('suggestions.defaults.include_substitutions', true)
            ),
        ]);
    }

    public function rules(): array
    {
        return [
            'goal' => ['nullable', 'string', Rule::in(RecipeType::values())],
            'recipe_type' => ['sometimes', 'nullable', 'string', Rule::in(RecipeType::values())],
            'pantry_item_ids' => [
                'sometimes',
                'array',
                'max:'.config('suggestions.limits.max_selected_pantry_items', 25),
            ],
            'pantry_item_ids.*' => ['string', 'ulid'],
            'limit' => [
                'sometimes',
                'integer',
                'min:1',
                'max:'.config('suggestions.limits.max_results', 10),
            ],
            'include_substitutions' => ['sometimes', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $pantryItemIds = $this->selectedPantryItemIds();

            if ($pantryItemIds === [] || ! $this->user()) {
                return;
            }

            $validCount = PantryItem::query()
                ->ownedBy($this->user())
                ->active()
                ->whereIn('id', $pantryItemIds)
                ->count();

            if ($validCount === count($pantryItemIds)) {
                return;
            }

            $validator->errors()->add(
                'pantry_item_ids',
                'The selected pantry items are invalid.'
            );
        });
    }

    public function payload(): GenerateSuggestionsData
    {
        return GenerateSuggestionsData::fromValidated([
            'goal' => $this->goal(),
            'pantry_item_ids' => $this->selectedPantryItemIds(),
            'limit' => $this->resultLimit(),
            'include_substitutions' => $this->boolean(
                'include_substitutions',
                (bool) config('suggestions.defaults.include_substitutions', true)
            ),
        ]);
    }

    public function goal(): ?string
    {
        return $this->filled('goal')
            ? $this->string('goal')->toString()
            : null;
    }

    public function selectedPantryItemIds(): array
    {
        return collect(Arr::wrap($this->input('pantry_item_ids')))
            ->map(fn ($value) => trim((string) $value))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    public function resultLimit(): int
    {
        $maxResults = (int) config('suggestions.limits.max_results', 10);

        return max(1, min($this->integer('limit', (int) config('suggestions.defaults.limit', 5)), $maxResults));
    }
}
