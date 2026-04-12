<?php

namespace App\Modules\Pantry\Application\DTO;

use Illuminate\Support\Arr;

final readonly class UpdatePantryItemData
{
    private function __construct(
        public ?float $quantity,
        public ?string $unit,
        public ?string $note,
        public ?string $expiresOn,
        private array $attributes,
    ) {}

    public static function fromValidated(array $validated): self
    {
        $attributes = Arr::only($validated, [
            'quantity',
            'unit',
            'note',
            'expires_on',
        ]);

        return new self(
            quantity: array_key_exists('quantity', $attributes) && $attributes['quantity'] !== null
                ? (float) $attributes['quantity']
                : null,
            unit: $attributes['unit'] ?? null,
            note: $attributes['note'] ?? null,
            expiresOn: $attributes['expires_on'] ?? null,
            attributes: $attributes,
        );
    }

    public function attributes(): array
    {
        return $this->attributes;
    }
}
