<?php

namespace App\Modules\Recipes\Domain\Enums;

enum RecipeDifficulty: string
{
    case Easy = 'easy';
    case Medium = 'medium';
    case Hard = 'hard';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
