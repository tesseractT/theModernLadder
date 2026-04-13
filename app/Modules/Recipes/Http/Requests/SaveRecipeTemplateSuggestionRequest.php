<?php

namespace App\Modules\Recipes\Http\Requests;

use App\Modules\Recipes\Application\DTO\SaveRecipeTemplateSuggestionData;
use App\Modules\Recipes\Domain\Enums\RecipeTemplateInteractionSource;
use App\Modules\Recipes\Domain\Enums\RecipeType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SaveRecipeTemplateSuggestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'source' => $this->filled('source')
                ? Str::lower(trim((string) $this->input('source')))
                : RecipeTemplateInteractionSource::Suggestions->value,
            'goal' => $this->filled('goal')
                ? Str::lower(trim((string) $this->input('goal')))
                : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'source' => ['sometimes', 'string', Rule::in(RecipeTemplateInteractionSource::values())],
            'goal' => ['sometimes', 'nullable', 'string', Rule::in(RecipeType::values())],
        ];
    }

    public function payload(): SaveRecipeTemplateSuggestionData
    {
        return SaveRecipeTemplateSuggestionData::fromValidated($this->validated());
    }
}
