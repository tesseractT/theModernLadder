<?php

namespace App\Modules\Recipes\Http\Requests;

use App\Modules\Shared\Http\Requests\PaginatedIndexRequest;

class ListRecipeTemplateInteractionsRequest extends PaginatedIndexRequest
{
    public function rules(): array
    {
        return $this->paginationRules();
    }
}
