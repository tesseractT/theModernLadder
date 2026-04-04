<?php

namespace App\Modules\AI\Application\Exceptions;

use RuntimeException;
use Throwable;

class RecipeExplanationUnavailableException extends RuntimeException
{
    public function __construct(
        string $message = 'Unable to generate a recipe explanation right now.',
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
