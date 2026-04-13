<?php

namespace App\Modules\Moderation\Http\Requests;

use App\Modules\Moderation\Application\DTO\ModerateContributionData;
use App\Modules\Moderation\Domain\Enums\ModerationActionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ModerateContributionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'action' => trim((string) $this->input('action')),
            'notes' => trim((string) $this->input('notes')),
        ]);
    }

    public function rules(): array
    {
        return [
            'action' => ['required', 'string', Rule::in(ModerationActionType::reviewValues())],
            'notes' => ['required', 'string', 'max:1000'],
        ];
    }

    public function payload(): ModerateContributionData
    {
        return ModerateContributionData::fromValidated($this->validated());
    }
}
