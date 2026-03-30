<?php

namespace App\Modules\Recipes\Domain\Enums;

enum RecipeType: string
{
    case Drink = 'drink';
    case Breakfast = 'breakfast';
    case Snack = 'snack';
    case Dessert = 'dessert';
    case LightMeal = 'light_meal';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
