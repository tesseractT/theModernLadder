<?php

namespace App\Modules\AI\Application\Exceptions;

use RuntimeException;
use Throwable;

class InvalidRecipeExplanationException extends RuntimeException
{
    public function __construct(
        string $message = 'Recipe explanation output was invalid.',
        public array $context = [],
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
