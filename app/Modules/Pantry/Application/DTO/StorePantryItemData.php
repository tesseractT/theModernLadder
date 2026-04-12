<?php

namespace App\Modules\Pantry\Application\DTO;

final readonly class StorePantryItemData
{
    public function __construct(
        public string $ingredientId,
        public ?float $quantity,
        public ?string $unit,
        public ?string $note,
        public ?string $expiresOn,
    ) {}

    public static function fromValidated(array $validated): self
    {
        return new self(
            ingredientId: (string) $validated['ingredient_id'],
            quantity: isset($validated['quantity']) ? (float) $validated['quantity'] : null,
            unit: $validated['unit'] ?? null,
            note: $validated['note'] ?? null,
            expiresOn: $validated['expires_on'] ?? null,
        );
    }
}
