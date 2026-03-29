<?php

namespace App\Modules\Contributions\Domain\Enums;

enum ContributionStatus: string
{
    case Submitted = 'submitted';
    case Accepted = 'accepted';
    case Rejected = 'rejected';
    case Withdrawn = 'withdrawn';
}
