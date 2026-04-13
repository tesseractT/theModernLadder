<?php

namespace App\Modules\Moderation\Http\Requests;

use App\Modules\Contributions\Domain\Enums\ContributionStatus;
use App\Modules\Shared\Http\Requests\PaginatedIndexRequest;
use Illuminate\Validation\Rule;

class ListModerationContributionsRequest extends PaginatedIndexRequest
{
    public function rules(): array
    {
        return array_merge($this->paginationRules(), [
            'status' => ['sometimes', 'string', Rule::in([
                ContributionStatus::Pending->value,
                ContributionStatus::Flagged->value,
                'all',
            ])],
        ]);
    }

    public function statusFilter(): ?ContributionStatus
    {
        if (! $this->filled('status')) {
            return null;
        }

        $status = trim((string) $this->input('status'));

        if ($status === 'all') {
            return null;
        }

        return ContributionStatus::from($status);
    }
}
