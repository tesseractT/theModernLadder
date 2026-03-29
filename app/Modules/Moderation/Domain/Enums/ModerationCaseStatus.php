<?php

namespace App\Modules\Moderation\Domain\Enums;

enum ModerationCaseStatus: string
{
    case Open = 'open';
    case UnderReview = 'under_review';
    case Resolved = 'resolved';
    case Dismissed = 'dismissed';
}
