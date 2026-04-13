<?php

namespace App\Modules\Moderation\Domain\Enums;

use App\Modules\Contributions\Domain\Enums\ContributionStatus;

enum ModerationActionType: string
{
    case Reported = 'reported';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Flagged = 'flagged';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function reviewValues(): array
    {
        return [
            self::Approved->value,
            self::Rejected->value,
            self::Flagged->value,
        ];
    }

    public function targetStatus(): ?ContributionStatus
    {
        return match ($this) {
            self::Reported => null,
            self::Approved => ContributionStatus::Approved,
            self::Rejected => ContributionStatus::Rejected,
            self::Flagged => ContributionStatus::Flagged,
        };
    }
}
