<?php

namespace App\Modules\Recipes\Application\DTO;

use App\Modules\Recipes\Domain\Enums\RecipePlanHorizon;
use Illuminate\Support\Arr;

final readonly class UpdateRecipePlanItemData
{
    private function __construct(
        public ?RecipePlanHorizon $horizon,
        public ?string $note,
        private array $attributes,
    ) {}

    public static function fromValidated(array $validated): self
    {
        $attributes = Arr::only($validated, [
            'horizon',
            'note',
        ]);

        return new self(
            horizon: array_key_exists('horizon', $attributes) && $attributes['horizon'] !== null
                ? RecipePlanHorizon::from((string) $attributes['horizon'])
                : null,
            note: $attributes['note'] ?? null,
            attributes: $attributes,
        );
    }

    public function attributes(): array
    {
        return $this->attributes;
    }
}
