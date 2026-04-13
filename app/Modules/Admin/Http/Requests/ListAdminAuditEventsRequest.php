<?php

namespace App\Modules\Admin\Http\Requests;

use App\Modules\Shared\Http\Requests\PaginatedIndexRequest;

class ListAdminAuditEventsRequest extends PaginatedIndexRequest
{
    public function rules(): array
    {
        return array_merge($this->paginationRules(), [
            'event' => ['sometimes', 'string', 'max:120'],
            'actor_user_id' => ['sometimes', 'string'],
        ]);
    }

    public function eventFilter(): ?string
    {
        return $this->filled('event')
            ? trim((string) $this->input('event'))
            : null;
    }

    public function actorUserId(): ?string
    {
        return $this->filled('actor_user_id')
            ? trim((string) $this->input('actor_user_id'))
            : null;
    }
}
