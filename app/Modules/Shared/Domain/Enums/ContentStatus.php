<?php

namespace App\Modules\Shared\Domain\Enums;

enum ContentStatus: string
{
    case Draft = 'draft';
    case PendingReview = 'pending_review';
    case Published = 'published';
    case Archived = 'archived';
}
