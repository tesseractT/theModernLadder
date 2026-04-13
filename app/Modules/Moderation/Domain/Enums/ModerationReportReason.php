<?php

namespace App\Modules\Moderation\Domain\Enums;

enum ModerationReportReason: string
{
    case Incorrect = 'incorrect';
    case Unsafe = 'unsafe';
    case Spam = 'spam';
    case Abusive = 'abusive';
    case Other = 'other';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
