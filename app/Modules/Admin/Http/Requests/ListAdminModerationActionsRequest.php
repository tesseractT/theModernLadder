<?php

namespace App\Modules\Admin\Http\Requests;

use App\Modules\Moderation\Domain\Enums\ModerationActionType;
use App\Modules\Shared\Http\Requests\PaginatedIndexRequest;
use Illuminate\Validation\Rule;

class ListAdminModerationActionsRequest extends PaginatedIndexRequest
{
    public function rules(): array
    {
        return array_merge($this->paginationRules(), [
            'action' => ['sometimes', 'string', Rule::in(ModerationActionType::values())],
            'actor_user_id' => ['sometimes', 'string'],
        ]);
    }

    public function actionFilter(): ?ModerationActionType
    {
        if (! $this->filled('action')) {
            return null;
        }

        return ModerationActionType::from(trim((string) $this->input('action')));
    }

    public function actorUserId(): ?string
    {
        return $this->filled('actor_user_id')
            ? trim((string) $this->input('actor_user_id'))
            : null;
    }
}
