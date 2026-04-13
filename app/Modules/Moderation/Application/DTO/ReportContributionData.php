<?php

namespace App\Modules\Moderation\Application\DTO;

use App\Modules\Moderation\Domain\Enums\ModerationReportReason;

final readonly class ReportContributionData
{
    public function __construct(
        public ModerationReportReason $reason,
        public ?string $notes,
    ) {}

    public static function fromValidated(array $validated): self
    {
        return new self(
            reason: ModerationReportReason::from((string) $validated['reason_code']),
            notes: $validated['notes'] ?? null,
        );
    }
}
