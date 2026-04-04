<?php

namespace App\Modules\AI\Application\DTO;

final readonly class RecipeExplanationProviderResponse
{
    public function __construct(
        public array $payload,
        public string $provider,
        public string $model,
        public int $latencyMs,
    ) {}
}
