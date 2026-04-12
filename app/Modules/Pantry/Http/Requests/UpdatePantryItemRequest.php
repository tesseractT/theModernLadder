<?php

namespace App\Modules\Pantry\Http\Requests;

use App\Modules\Pantry\Application\DTO\UpdatePantryItemData;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePantryItemRequest extends FormRequest
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

        $this->merge($normalized);
    }

    public function rules(): array
    {
        return [
            'quantity' => ['sometimes', 'nullable', 'numeric', 'gt:0', 'max:999999.99'],
            'unit' => ['sometimes', 'nullable', 'string', 'max:32'],
            'note' => ['sometimes', 'nullable', 'string', 'max:500'],
            'expires_on' => ['sometimes', 'nullable', 'date'],
        ];
    }

    public function payload(): UpdatePantryItemData
    {
        return UpdatePantryItemData::fromValidated(
            $this->safe()->only([
                'quantity',
                'unit',
                'note',
                'expires_on',
            ])
        );
    }
}
