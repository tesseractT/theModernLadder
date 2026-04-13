<?php

namespace App\Modules\Moderation\Domain\Enums;

enum ModerationCaseStatus: string
{
    case Open = 'open';
    case UnderReview = 'under_review';
    case Resolved = 'resolved';
    case Dismissed = 'dismissed';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function activeValues(): array
    {
        return [
            self::Open->value,
            self::UnderReview->value,
        ];
    }

    public function isActive(): bool
    {
        return in_array($this, [self::Open, self::UnderReview], true);
    }
}
