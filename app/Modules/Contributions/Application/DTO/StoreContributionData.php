<?php

namespace App\Modules\Contributions\Application\DTO;

use App\Modules\Contributions\Domain\Enums\ContributionSubjectType;
use App\Modules\Contributions\Domain\Enums\ContributionType;

final readonly class StoreContributionData
{
    public function __construct(
        public ContributionType $type,
        public ContributionSubjectType $subjectType,
        public string $subjectId,
        public array $payload,
    ) {}

    public static function fromValidated(array $validated): self
    {
        $type = ContributionType::from((string) $validated['type']);
        $payload = is_array($validated['payload'] ?? null) ? $validated['payload'] : [];

        return new self(
            type: $type,
            subjectType: ContributionSubjectType::from((string) $validated['subject_type']),
            subjectId: (string) $validated['subject_id'],
            payload: self::filterPayload(match ($type) {
                ContributionType::RecipeTemplateChange => [
                    'summary' => $payload['summary'] ?? null,
                    'proposed_title' => $payload['proposed_title'] ?? null,
                    'proposed_summary' => $payload['proposed_summary'] ?? null,
                    'proposed_instructions' => $payload['proposed_instructions'] ?? null,
                ],
                ContributionType::PairingTip => [
                    'paired_ingredient_id' => $payload['paired_ingredient_id'] ?? null,
                    'strength' => $payload['strength'] ?? null,
                    'note' => $payload['note'] ?? null,
                ],
                ContributionType::SubstitutionTip => [
                    'substitute_ingredient_id' => $payload['substitute_ingredient_id'] ?? null,
                    'note' => $payload['note'] ?? null,
                ],
                ContributionType::IngredientAliasCorrection => [
                    'alias' => $payload['alias'] ?? null,
                    'locale' => $payload['locale'] ?? null,
                    'note' => $payload['note'] ?? null,
                ],
            }),
        );
    }

    protected static function filterPayload(array $payload): array
    {
        return array_filter($payload, fn (mixed $value): bool => $value !== null && $value !== '');
    }
}
