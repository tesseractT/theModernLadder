<?php

namespace App\Modules\Recipes\Domain\Enums;

enum RecipeTemplateInteractionType: string
{
    case Favorite = 'favorite';
    case SavedSuggestion = 'saved_suggestion';
    case RecentHistory = 'recent_history';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
