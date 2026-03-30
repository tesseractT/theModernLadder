<?php

namespace Database\Seeders;

use App\Modules\Ingredients\Domain\Models\Ingredient;
use App\Modules\Ingredients\Domain\Models\Pairing;
use App\Modules\Ingredients\Domain\Models\Substitution;
use App\Modules\Recipes\Domain\Models\RecipeTemplate;
use App\Modules\Recipes\Domain\Models\RecipeTemplateIngredient;
use App\Modules\Recipes\Domain\Models\RecipeTemplateStep;
use App\Modules\Shared\Domain\Enums\ContentStatus;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StarterRecipeTemplateCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $catalog = require database_path('seeders/data/starter_recipe_templates.php');

        DB::transaction(function () use ($catalog): void {
            $ingredientsBySlug = $this->seedIngredients($catalog['ingredients'] ?? []);

            $this->seedTemplates($catalog['templates'] ?? [], $ingredientsBySlug);
            $this->seedSubstitutions($catalog['substitutions'] ?? [], $ingredientsBySlug);
            $this->seedPairings($catalog['pairings'] ?? [], $ingredientsBySlug);
        });
    }

    protected function seedIngredients(array $ingredients): array
    {
        $ingredientsBySlug = [];

        foreach ($ingredients as $ingredientData) {
            $ingredient = Ingredient::query()
                ->withTrashed()
                ->firstOrNew(['slug' => $ingredientData['slug']]);

            $ingredient->fill([
                'name' => $ingredientData['name'],
                'slug' => $ingredientData['slug'],
                'description' => $ingredientData['description'] ?? null,
                'status' => ContentStatus::Published,
            ]);
            $ingredient->save();

            if ($ingredient->trashed()) {
                $ingredient->restore();
            }

            $ingredientsBySlug[$ingredient->slug] = $ingredient;
        }

        return $ingredientsBySlug;
    }

    protected function seedTemplates(array $templates, array $ingredientsBySlug): void
    {
        foreach ($templates as $templateData) {
            $steps = $templateData['steps'] ?? [];

            $template = RecipeTemplate::query()
                ->withTrashed()
                ->firstOrNew(['slug' => $templateData['slug']]);

            $template->fill([
                'title' => $templateData['title'],
                'slug' => $templateData['slug'],
                'recipe_type' => $templateData['recipe_type'] ?? null,
                'difficulty' => $templateData['difficulty'] ?? null,
                'dietary_patterns' => $templateData['dietary_patterns'] ?? [],
                'summary' => $templateData['summary'] ?? null,
                'instructions' => $steps !== [] ? implode("\n\n", $steps) : null,
                'servings' => $templateData['servings'] ?? null,
                'prep_minutes' => $templateData['prep_minutes'] ?? null,
                'cook_minutes' => $templateData['cook_minutes'] ?? null,
                'status' => ContentStatus::Published,
            ]);
            $template->save();

            if ($template->trashed()) {
                $template->restore();
            }

            RecipeTemplateIngredient::query()
                ->where('recipe_template_id', $template->id)
                ->delete();

            $position = 1;

            foreach ($templateData['required_ingredients'] ?? [] as $ingredientSlug) {
                RecipeTemplateIngredient::query()->create([
                    'recipe_template_id' => $template->id,
                    'ingredient_id' => $ingredientsBySlug[$ingredientSlug]->id,
                    'is_required' => true,
                    'sort_order' => $position++,
                ]);
            }

            foreach ($templateData['optional_ingredients'] ?? [] as $ingredientSlug) {
                RecipeTemplateIngredient::query()->create([
                    'recipe_template_id' => $template->id,
                    'ingredient_id' => $ingredientsBySlug[$ingredientSlug]->id,
                    'is_required' => false,
                    'sort_order' => $position++,
                ]);
            }

            RecipeTemplateStep::query()
                ->where('recipe_template_id', $template->id)
                ->delete();

            foreach ($steps as $index => $instruction) {
                RecipeTemplateStep::query()->create([
                    'recipe_template_id' => $template->id,
                    'position' => $index + 1,
                    'instruction' => $instruction,
                ]);
            }
        }
    }

    protected function seedSubstitutions(array $substitutions, array $ingredientsBySlug): void
    {
        foreach ($substitutions as $substitutionData) {
            $substitution = Substitution::query()
                ->withTrashed()
                ->firstOrNew([
                    'ingredient_id' => $ingredientsBySlug[$substitutionData['ingredient']]->id,
                    'substitute_ingredient_id' => $ingredientsBySlug[$substitutionData['substitute_ingredient']]->id,
                ]);

            $substitution->fill([
                'note' => $substitutionData['note'] ?? null,
                'status' => ContentStatus::Published,
            ]);
            $substitution->save();

            if ($substitution->trashed()) {
                $substitution->restore();
            }
        }
    }

    protected function seedPairings(array $pairings, array $ingredientsBySlug): void
    {
        foreach ($pairings as $pairingData) {
            $pairing = Pairing::query()
                ->withTrashed()
                ->firstOrNew([
                    'ingredient_id' => $ingredientsBySlug[$pairingData['ingredient']]->id,
                    'paired_ingredient_id' => $ingredientsBySlug[$pairingData['paired_ingredient']]->id,
                ]);

            $pairing->fill([
                'strength' => $pairingData['strength'] ?? null,
                'note' => $pairingData['note'] ?? null,
                'status' => ContentStatus::Published,
            ]);
            $pairing->save();

            if ($pairing->trashed()) {
                $pairing->restore();
            }
        }
    }
}
