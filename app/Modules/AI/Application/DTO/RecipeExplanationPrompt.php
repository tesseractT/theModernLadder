<?php

namespace App\Modules\AI\Application\DTO;

final readonly class RecipeExplanationPrompt
{
    public function __construct(
        public string $instructions,
        public string $input,
        public string $schemaName,
        public array $schema,
    ) {}
}
