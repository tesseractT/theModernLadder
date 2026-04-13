<?php

namespace App\Modules\Recipes\Http\Requests;

use App\Modules\Recipes\Application\DTO\StoreRecipePlanItemData;
use App\Modules\Recipes\Domain\Enums\RecipePlanHorizon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreRecipePlanItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'recipe_template_id' => trim((string) $this->input('recipe_template_id')),
            'horizon' => $this->filled('horizon')
                ? Str::lower(trim((string) $this->input('horizon')))
                : null,
            'note' => $this->filled('note')
                ? Str::squish((string) $this->input('note'))
                : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'recipe_template_id' => ['required', 'string', 'ulid'],
            'horizon' => ['required', 'string', Rule::in(RecipePlanHorizon::values())],
            'note' => ['sometimes', 'nullable', 'string', 'max:240'],
        ];
    }

    public function payload(): StoreRecipePlanItemData
    {
        return StoreRecipePlanItemData::fromValidated($this->validated());
    }
}
