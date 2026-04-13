<?php

namespace App\Modules\Contributions\Domain\Enums;

enum ContributionAction: string
{
    case Create = 'create';
    case Update = 'update';
    case Report = 'report';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
