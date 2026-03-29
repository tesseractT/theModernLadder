<?php

namespace App\Modules\Contributions\Domain\Enums;

enum ContributionAction: string
{
    case Create = 'create';
    case Update = 'update';
    case Report = 'report';
}
