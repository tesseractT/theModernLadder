<?php

namespace App\Modules\Recipes\Application\DTO;

final readonly class GenerateSuggestionsData
{
    public function __construct(
        public ?string $goal,
        public array $pantryItemIds,
        public int $limit,
        public bool $includeSubstitutions,
    ) {}

    public static function fromValidated(array $validated): self
    {
        return new self(
            goal: $validated['goal'] ?? null,
            pantryItemIds: $validated['pantry_item_ids'] ?? [],
            limit: (int) ($validated['limit'] ?? config('suggestions.defaults.limit', 5)),
            includeSubstitutions: (bool) ($validated['include_substitutions']
                ?? config('suggestions.defaults.include_substitutions', true)),
        );
    }

    public function toResponseArray(): array
    {
        return [
            'goal' => $this->goal,
            'limit' => $this->limit,
            'include_substitutions' => $this->includeSubstitutions,
            'pantry_item_ids' => $this->pantryItemIds,
        ];
    }
}
