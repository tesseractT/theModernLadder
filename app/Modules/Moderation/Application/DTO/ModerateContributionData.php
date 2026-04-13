<?php

namespace App\Modules\Moderation\Application\DTO;

use App\Modules\Contributions\Domain\Enums\ContributionStatus;
use App\Modules\Moderation\Domain\Enums\ModerationActionType;

final readonly class ModerateContributionData
{
    public function __construct(
        public ModerationActionType $action,
        public string $notes,
    ) {}

    public static function fromValidated(array $validated): self
    {
        return new self(
            action: ModerationActionType::from((string) $validated['action']),
            notes: (string) $validated['notes'],
        );
    }

    public function targetStatus(): ContributionStatus
    {
        return $this->action->targetStatus()
            ?? throw new \LogicException('Review actions must resolve to a contribution status.');
    }
}
