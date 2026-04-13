<?php

namespace App\Modules\Contributions\Domain\Enums;

enum ContributionType: string
{
    case RecipeTemplateChange = 'recipe_template_change';
    case PairingTip = 'pairing_tip';
    case SubstitutionTip = 'substitution_tip';
    case IngredientAliasCorrection = 'ingredient_alias_correction';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function action(): ContributionAction
    {
        return match ($this) {
            self::RecipeTemplateChange => ContributionAction::Update,
            self::PairingTip, self::SubstitutionTip, self::IngredientAliasCorrection => ContributionAction::Create,
        };
    }

    public function subjectType(): ContributionSubjectType
    {
        return match ($this) {
            self::RecipeTemplateChange => ContributionSubjectType::RecipeTemplate,
            self::PairingTip, self::SubstitutionTip, self::IngredientAliasCorrection => ContributionSubjectType::Ingredient,
        };
    }
}
