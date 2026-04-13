<?php

namespace App\Modules\Recipes\Application\DTO;

use App\Modules\Recipes\Domain\Enums\RecipePlanHorizon;

final readonly class StoreRecipePlanItemData
{
    public function __construct(
        public string $recipeTemplateId,
        public RecipePlanHorizon $horizon,
        public ?string $note,
    ) {}

    public static function fromValidated(array $validated): self
    {
        return new self(
            recipeTemplateId: (string) $validated['recipe_template_id'],
            horizon: RecipePlanHorizon::from((string) $validated['horizon']),
            note: $validated['note'] ?? null,
        );
    }
}
