<?php

namespace App\Modules\AI\Application\Exceptions;

use RuntimeException;
use Throwable;

class RecipeExplanationProviderException extends RuntimeException
{
    public function __construct(
        string $message = 'Recipe explanation provider request failed.',
        public array $context = [],
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
