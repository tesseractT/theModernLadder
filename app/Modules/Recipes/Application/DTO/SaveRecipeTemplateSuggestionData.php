<?php

namespace App\Modules\Recipes\Application\DTO;

use App\Modules\Recipes\Domain\Enums\RecipeTemplateInteractionSource;

final readonly class SaveRecipeTemplateSuggestionData
{
    public function __construct(
        public RecipeTemplateInteractionSource $source,
        public ?string $goal,
    ) {}

    public static function fromValidated(array $validated): self
    {
        return new self(
            source: RecipeTemplateInteractionSource::from(
                $validated['source'] ?? RecipeTemplateInteractionSource::Suggestions->value
            ),
            goal: $validated['goal'] ?? null,
        );
    }
}
