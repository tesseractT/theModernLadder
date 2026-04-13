<?php

namespace App\Modules\Recipes\Http\Requests;

use App\Modules\Recipes\Application\DTO\UpdateRecipePlanItemData;
use App\Modules\Recipes\Domain\Enums\RecipePlanHorizon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateRecipePlanItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $attributes = [];

        if ($this->has('horizon')) {
            $attributes['horizon'] = $this->filled('horizon')
                ? Str::lower(trim((string) $this->input('horizon')))
                : null;
        }

        if ($this->has('note')) {
            $attributes['note'] = $this->filled('note')
                ? Str::squish((string) $this->input('note'))
                : null;
        }

        if ($attributes !== []) {
            $this->merge($attributes);
        }
    }

    public function rules(): array
    {
        return [
            'horizon' => ['sometimes', 'nullable', 'string', Rule::in(RecipePlanHorizon::values())],
            'note' => ['sometimes', 'nullable', 'string', 'max:240'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->hasAny(['horizon', 'note'])) {
                return;
            }

            $validator->errors()->add(
                'payload',
                'At least one updatable field is required.'
            );
        });
    }

    public function payload(): UpdateRecipePlanItemData
    {
        return UpdateRecipePlanItemData::fromValidated($this->validated());
    }
}
