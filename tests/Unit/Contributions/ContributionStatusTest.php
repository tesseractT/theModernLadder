<?php

namespace Tests\Unit\Contributions;

use App\Modules\Contributions\Domain\Enums\ContributionStatus;
use PHPUnit\Framework\TestCase;

class ContributionStatusTest extends TestCase
{
    public function test_explicit_moderation_transitions_are_limited_to_the_supported_state_machine(): void
    {
        $this->assertTrue(ContributionStatus::Pending->canTransitionTo(ContributionStatus::Approved));
        $this->assertTrue(ContributionStatus::Pending->canTransitionTo(ContributionStatus::Rejected));
        $this->assertTrue(ContributionStatus::Approved->canTransitionTo(ContributionStatus::Flagged));
        $this->assertTrue(ContributionStatus::Flagged->canTransitionTo(ContributionStatus::Approved));
        $this->assertTrue(ContributionStatus::Flagged->canTransitionTo(ContributionStatus::Rejected));

        $this->assertFalse(ContributionStatus::Pending->canTransitionTo(ContributionStatus::Flagged));
        $this->assertFalse(ContributionStatus::Approved->canTransitionTo(ContributionStatus::Approved));
        $this->assertFalse(ContributionStatus::Rejected->canTransitionTo(ContributionStatus::Approved));
        $this->assertFalse(ContributionStatus::Flagged->canTransitionTo(ContributionStatus::Flagged));
    }

    public function test_only_approved_and_flagged_contributions_are_reportable(): void
    {
        $this->assertFalse(ContributionStatus::Pending->canBeReported());
        $this->assertTrue(ContributionStatus::Approved->canBeReported());
        $this->assertFalse(ContributionStatus::Rejected->canBeReported());
        $this->assertTrue(ContributionStatus::Flagged->canBeReported());
    }
}
