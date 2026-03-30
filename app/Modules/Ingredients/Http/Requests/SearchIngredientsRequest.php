<?php

namespace App\Modules\Ingredients\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class SearchIngredientsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'q' => trim((string) $this->input('q')),
            'limit' => $this->integer('limit', 10),
        ]);
    }

    public function rules(): array
    {
        return [
            'q' => ['required', 'string', 'min:2', 'max:100'],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:25'],
        ];
    }

    public function queryTerm(): string
    {
        return Str::lower($this->string('q')->toString());
    }

    public function resultLimit(): int
    {
        return max(1, min($this->integer('limit', 10), 25));
    }
}
