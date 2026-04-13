<?php

namespace App\Modules\Contributions\Domain\Enums;

enum ContributionStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Flagged = 'flagged';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function reviewQueueValues(): array
    {
        return [
            self::Pending->value,
            self::Flagged->value,
        ];
    }

    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Pending => [self::Approved, self::Rejected],
            self::Approved => [self::Flagged],
            self::Rejected => [],
            self::Flagged => [self::Approved, self::Rejected],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }

    public function canBeReported(): bool
    {
        return in_array($this, [self::Approved, self::Flagged], true);
    }
}
