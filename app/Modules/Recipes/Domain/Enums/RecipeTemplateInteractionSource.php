<?php

namespace App\Modules\Recipes\Domain\Enums;

enum RecipeTemplateInteractionSource: string
{
    case Suggestions = 'suggestions';
    case RecipeDetail = 'recipe_detail';
    case RecipeExplanation = 'recipe_explanation';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
