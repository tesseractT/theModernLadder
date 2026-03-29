<?php

namespace App\Modules\Shared\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

abstract class PaginatedIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function paginationRules(): array
    {
        return [
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:'.config('api.max_per_page')],
        ];
    }

    public function perPage(): int
    {
        $perPage = $this->integer('per_page', (int) config('api.per_page'));

        return max(1, min($perPage, (int) config('api.max_per_page')));
    }
}
