<?php

namespace App\Modules\Contributions\Http\Requests;

use App\Modules\Contributions\Application\DTO\StoreContributionData;
use App\Modules\Contributions\Domain\Enums\ContributionSubjectType;
use App\Modules\Contributions\Domain\Enums\ContributionType;
use App\Modules\Shared\Domain\Enums\ContentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreContributionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $payload = $this->input('payload', []);
        $payload = is_array($payload) ? $payload : [];

        foreach ([
            'summary',
            'proposed_title',
            'proposed_summary',
            'proposed_instructions',
            'note',
            'alias',
            'locale',
        ] as $field) {
            if (array_key_exists($field, $payload)) {
                $payload[$field] = $payload[$field] === null
                    ? null
                    : trim((string) $payload[$field]);
            }
        }

        foreach ([
            'paired_ingredient_id',
            'substitute_ingredient_id',
        ] as $field) {
            if (array_key_exists($field, $payload)) {
                $payload[$field] = trim((string) $payload[$field]);
            }
        }

        if (array_key_exists('strength', $payload) && $payload['strength'] !== null && $payload['strength'] !== '') {
            $payload['strength'] = (int) $payload['strength'];
        }

        $this->merge([
            'type' => trim((string) $this->input('type')),
            'subject_type' => trim((string) $this->input('subject_type')),
            'subject_id' => trim((string) $this->input('subject_id')),
            'payload' => $payload,
        ]);
    }

    public function rules(): array
    {
        $rules = [
            'type' => ['required', 'string', Rule::in(ContributionType::values())],
            'subject_type' => ['required', 'string', Rule::in(ContributionSubjectType::values())],
            'subject_id' => ['required', 'string'],
            'payload' => ['required', 'array'],
        ];

        $subjectType = ContributionSubjectType::tryFrom((string) $this->input('subject_type'));

        if ($subjectType !== null) {
            $rules['subject_id'][] = $this->publishedSubjectExistsRule($subjectType);
        }

        return array_merge($rules, $this->typeSpecificRules());
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $type = ContributionType::tryFrom((string) $this->input('type'));
            $subjectType = ContributionSubjectType::tryFrom((string) $this->input('subject_type'));

            if ($type !== null && $subjectType !== null && $type->subjectType() !== $subjectType) {
                $validator->errors()->add('subject_type', 'The selected subject type does not match this contribution type.');
            }

            if ($type === ContributionType::RecipeTemplateChange) {
                $payload = is_array($this->input('payload')) ? $this->input('payload') : [];

                if (blank($payload['proposed_title'] ?? null)
                    && blank($payload['proposed_summary'] ?? null)
                    && blank($payload['proposed_instructions'] ?? null)) {
                    $validator->errors()->add('payload', 'Provide at least one proposed recipe template change.');
                }
            }
        });
    }

    public function payload(): StoreContributionData
    {
        return StoreContributionData::fromValidated($this->validated());
    }

    protected function typeSpecificRules(): array
    {
        $type = ContributionType::tryFrom((string) $this->input('type'));

        return match ($type) {
            ContributionType::RecipeTemplateChange => [
                'payload.summary' => ['required', 'string', 'max:1000'],
                'payload.proposed_title' => ['sometimes', 'nullable', 'string', 'max:200'],
                'payload.proposed_summary' => ['sometimes', 'nullable', 'string', 'max:2000'],
                'payload.proposed_instructions' => ['sometimes', 'nullable', 'string', 'max:5000'],
            ],
            ContributionType::PairingTip => [
                'payload.paired_ingredient_id' => [
                    'required',
                    'string',
                    'different:subject_id',
                    $this->publishedSubjectExistsRule(ContributionSubjectType::Ingredient),
                ],
                'payload.strength' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:5'],
                'payload.note' => ['required', 'string', 'max:500'],
            ],
            ContributionType::SubstitutionTip => [
                'payload.substitute_ingredient_id' => [
                    'required',
                    'string',
                    'different:subject_id',
                    $this->publishedSubjectExistsRule(ContributionSubjectType::Ingredient),
                ],
                'payload.note' => ['required', 'string', 'max:500'],
            ],
            ContributionType::IngredientAliasCorrection => [
                'payload.alias' => ['required', 'string', 'max:150'],
                'payload.locale' => ['sometimes', 'nullable', 'string', 'max:12'],
                'payload.note' => ['sometimes', 'nullable', 'string', 'max:500'],
            ],
            default => [],
        };
    }

    protected function publishedSubjectExistsRule(ContributionSubjectType $subjectType)
    {
        return match ($subjectType) {
            ContributionSubjectType::Ingredient => Rule::exists('ingredients', 'id')->where(function ($query): void {
                $query
                    ->where('status', ContentStatus::Published->value)
                    ->whereNull('deleted_at');
            }),
            ContributionSubjectType::RecipeTemplate => Rule::exists('recipe_templates', 'id')->where(function ($query): void {
                $query
                    ->where('status', ContentStatus::Published->value)
                    ->whereNull('deleted_at');
            }),
        };
    }
}
