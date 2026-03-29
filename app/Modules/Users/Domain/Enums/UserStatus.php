<?php

namespace App\Modules\Users\Domain\Enums;

enum UserStatus: string
{
    case Active = 'active';
    case Suspended = 'suspended';
}
