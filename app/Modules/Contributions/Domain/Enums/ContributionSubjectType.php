<?php

namespace App\Modules\Contributions\Domain\Enums;

use App\Modules\Ingredients\Domain\Models\Ingredient;
use App\Modules\Recipes\Domain\Models\RecipeTemplate;
use Illuminate\Database\Eloquent\Model;

enum ContributionSubjectType: string
{
    case Ingredient = 'ingredient';
    case RecipeTemplate = 'recipe_template';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function fromModel(Model|string|null $model): ?self
    {
        $className = $model instanceof Model ? $model::class : $model;

        return match ($className) {
            Ingredient::class => self::Ingredient,
            RecipeTemplate::class => self::RecipeTemplate,
            default => null,
        };
    }

    public function resolvePublishedSubject(string $id): Model
    {
        return match ($this) {
            self::Ingredient => Ingredient::query()
                ->published()
                ->findOrFail($id),
            self::RecipeTemplate => RecipeTemplate::query()
                ->published()
                ->findOrFail($id),
        };
    }
}
