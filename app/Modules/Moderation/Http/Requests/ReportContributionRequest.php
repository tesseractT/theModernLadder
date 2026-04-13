<?php

namespace App\Modules\Moderation\Http\Requests;

use App\Modules\Moderation\Application\DTO\ReportContributionData;
use App\Modules\Moderation\Domain\Enums\ModerationReportReason;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReportContributionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'reason_code' => trim((string) $this->input('reason_code')),
            'notes' => $this->exists('notes')
                ? ($this->input('notes') === null ? null : trim((string) $this->input('notes')))
                : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'reason_code' => ['required', 'string', Rule::in(ModerationReportReason::values())],
            'notes' => ['sometimes', 'nullable', 'string', 'max:500'],
        ];
    }

    public function payload(): ReportContributionData
    {
        return ReportContributionData::fromValidated($this->validated());
    }
}
