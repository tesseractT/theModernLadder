<?php

namespace App\Modules\Admin\Http\Requests;

use App\Modules\Contributions\Domain\Enums\ContributionSubjectType;
use App\Modules\Shared\Http\Requests\PaginatedIndexRequest;
use Illuminate\Validation\Rule;

class ListAdminFlaggedContributionsRequest extends PaginatedIndexRequest
{
    public function rules(): array
    {
        return array_merge($this->paginationRules(), [
            'reason_code' => ['sometimes', 'string', 'max:80'],
            'subject_type' => ['sometimes', 'string', Rule::in(ContributionSubjectType::values())],
        ]);
    }

    public function reasonCode(): ?string
    {
        return $this->filled('reason_code')
            ? trim((string) $this->input('reason_code'))
            : null;
    }

    public function subjectType(): ?ContributionSubjectType
    {
        if (! $this->filled('subject_type')) {
            return null;
        }

        return ContributionSubjectType::from(trim((string) $this->input('subject_type')));
    }
}
