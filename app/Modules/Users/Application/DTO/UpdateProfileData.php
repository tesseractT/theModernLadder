<?php

namespace App\Modules\Users\Application\DTO;

use Illuminate\Support\Arr;

final readonly class UpdateProfileData
{
    private function __construct(
        public ?string $displayName,
        public ?string $bio,
        public ?string $locale,
        public ?string $timezone,
        public ?string $countryCode,
        private array $attributes,
    ) {}

    public static function fromValidated(array $validated): self
    {
        $attributes = Arr::only($validated, [
            'display_name',
            'bio',
            'locale',
            'timezone',
            'country_code',
        ]);

        return new self(
            displayName: $attributes['display_name'] ?? null,
            bio: $attributes['bio'] ?? null,
            locale: $attributes['locale'] ?? null,
            timezone: $attributes['timezone'] ?? null,
            countryCode: $attributes['country_code'] ?? null,
            attributes: $attributes,
        );
    }

    public function attributes(): array
    {
        return $this->attributes;
    }
}
