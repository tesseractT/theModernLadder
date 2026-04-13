<?php

namespace App\Modules\Recipes\Domain\Enums;

enum RecipePlanHorizon: string
{
    case Today = 'today';
    case Tomorrow = 'tomorrow';
    case ThisWeek = 'this_week';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
