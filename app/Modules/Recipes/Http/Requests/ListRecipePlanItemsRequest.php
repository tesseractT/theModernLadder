<?php

namespace App\Modules\Recipes\Http\Requests;

use App\Modules\Recipes\Domain\Enums\RecipePlanHorizon;
use App\Modules\Shared\Http\Requests\PaginatedIndexRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ListRecipePlanItemsRequest extends PaginatedIndexRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'horizon' => $this->filled('horizon')
                ? Str::lower(trim((string) $this->input('horizon')))
                : null,
        ]);
    }

    public function rules(): array
    {
        return array_merge($this->paginationRules(), [
            'horizon' => ['sometimes', 'nullable', 'string', Rule::in(RecipePlanHorizon::values())],
        ]);
    }

    public function horizon(): ?RecipePlanHorizon
    {
        return $this->filled('horizon')
            ? RecipePlanHorizon::from((string) $this->input('horizon'))
            : null;
    }
}
