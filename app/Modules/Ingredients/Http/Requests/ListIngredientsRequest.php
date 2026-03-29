<?php

namespace App\Modules\Ingredients\Http\Requests;

use App\Modules\Shared\Http\Requests\PaginatedIndexRequest;

class ListIngredientsRequest extends PaginatedIndexRequest
{
    public function rules(): array
    {
        return array_merge($this->paginationRules(), [
            'search' => ['sometimes', 'string', 'max:100'],
        ]);
    }

    public function search(): ?string
    {
        return $this->filled('search')
            ? trim((string) $this->input('search'))
            : null;
    }
}
