<?php

namespace App\Modules\Recipes\Application\DTO;

use Illuminate\Support\Collection;

final readonly class GenerateSuggestionsResult
{
    public function __construct(
        public GenerateSuggestionsData $input,
        public Collection $pantryItems,
        public array $candidates,
        public ?string $message = null,
    ) {}
}
