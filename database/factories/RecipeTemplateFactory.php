<?php

namespace Database\Factories;

use App\Modules\Recipes\Domain\Enums\RecipeType;
use App\Modules\Recipes\Domain\Models\RecipeTemplate;
use App\Modules\Shared\Domain\Enums\ContentStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<RecipeTemplate>
 */
class RecipeTemplateFactory extends Factory
{
    protected $model = RecipeTemplate::class;

    public function definition(): array
    {
        $title = fake()->unique()->words(3, true);

        return [
            'title' => Str::title($title),
            'slug' => Str::slug($title),
            'recipe_type' => fake()->randomElement(RecipeType::values()),
            'dietary_patterns' => [],
            'summary' => fake()->sentence(12),
            'instructions' => implode("\n\n", fake()->paragraphs(3)),
            'servings' => fake()->numberBetween(1, 6),
            'prep_minutes' => fake()->numberBetween(5, 20),
            'cook_minutes' => fake()->numberBetween(10, 45),
            'status' => ContentStatus::Published,
            'created_by_user_id' => null,
        ];
    }
}
