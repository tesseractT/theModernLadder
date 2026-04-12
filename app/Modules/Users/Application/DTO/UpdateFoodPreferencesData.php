<?php

namespace App\Modules\Users\Application\DTO;

use Illuminate\Support\Arr;

final readonly class UpdateFoodPreferencesData
{
    private function __construct(
        public array $dietaryPatterns,
        public array $preferredCuisines,
        public array $dislikedIngredients,
        public ?string $measurementSystem,
        private array $attributes,
    ) {}

    public static function fromValidated(array $validated): self
    {
        $attributes = Arr::only($validated, [
            'dietary_patterns',
            'preferred_cuisines',
            'disliked_ingredients',
            'measurement_system',
        ]);

        return new self(
            dietaryPatterns: $attributes['dietary_patterns'] ?? [],
            preferredCuisines: $attributes['preferred_cuisines'] ?? [],
            dislikedIngredients: $attributes['disliked_ingredients'] ?? [],
            measurementSystem: $attributes['measurement_system'] ?? null,
            attributes: $attributes,
        );
    }

    public function attributes(): array
    {
        return $this->attributes;
    }
}
