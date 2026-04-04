<?php

namespace App\Modules\AI\Application\DTO;

final readonly class RecipeExplanationPayload
{
    public function __construct(
        public string $templateId,
        public string $source,
        public array $explanation,
        public array $meta,
    ) {}
}
