<?php

namespace App\Modules\Pantry\Domain\Enums;

enum PantryItemStatus: string
{
    case Active = 'active';
    case Archived = 'archived';
}
