<?php

namespace App\Modules\Pantry\Http\Requests;

use App\Modules\Shared\Domain\Enums\ContentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePantryItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $normalized = [];

        if ($this->exists('quantity')) {
            $normalized['quantity'] = $this->input('quantity') === null
                ? null
                : (float) $this->input('quantity');
        }

        if ($this->exists('unit')) {
            $normalized['unit'] = $this->input('unit') === null
                ? null
                : trim((string) $this->input('unit'));
        }

        if ($this->exists('note')) {
            $normalized['note'] = $this->input('note') === null
                ? null
                : trim((string) $this->input('note'));
        }

        if ($this->exists('expires_on')) {
            $normalized['expires_on'] = $this->input('expires_on') === null
                ? null
                : trim((string) $this->input('expires_on'));
        }

        if ($this->exists('ingredient_id')) {
            $normalized['ingredient_id'] = trim((string) $this->input('ingredient_id'));
        }

        $this->merge($normalized);
    }

    public function rules(): array
    {
        return [
            'ingredient_id' => [
                'required',
                'string',
                Rule::exists('ingredients', 'id')->where(function ($query): void {
                    $query
                        ->where('status', ContentStatus::Published->value)
                        ->whereNull('deleted_at');
                }),
            ],
            'quantity' => ['sometimes', 'nullable', 'numeric', 'gt:0', 'max:999999.99'],
            'unit' => ['sometimes', 'nullable', 'string', 'max:32'],
            'note' => ['sometimes', 'nullable', 'string', 'max:500'],
            'expires_on' => ['sometimes', 'nullable', 'date'],
        ];
    }

    public function pantryAttributes(): array
    {
        return $this->safe()->only([
            'ingredient_id',
            'quantity',
            'unit',
            'note',
            'expires_on',
        ]);
    }
}
