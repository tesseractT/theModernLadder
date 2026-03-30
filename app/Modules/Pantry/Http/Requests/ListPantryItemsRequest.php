<?php

namespace App\Modules\Pantry\Http\Requests;

use App\Modules\Shared\Http\Requests\PaginatedIndexRequest;

class ListPantryItemsRequest extends PaginatedIndexRequest
{
    public function rules(): array
    {
        return $this->paginationRules();
    }
}
