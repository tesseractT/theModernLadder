<?php

namespace App\Modules\Admin\Http\Requests;

use App\Modules\Shared\Http\Requests\PaginatedIndexRequest;

class ListAdminAiFailuresRequest extends PaginatedIndexRequest
{
    public function rules(): array
    {
        return array_merge($this->paginationRules(), [
            'template_id' => ['sometimes', 'string'],
        ]);
    }

    public function templateId(): ?string
    {
        return $this->filled('template_id')
            ? trim((string) $this->input('template_id'))
            : null;
    }
}
