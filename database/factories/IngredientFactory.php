<?php

namespace Database\Factories;

use App\Modules\Ingredients\Domain\Models\Ingredient;
use App\Modules\Shared\Domain\Enums\ContentStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Ingredient>
 */
class IngredientFactory extends Factory
{
    protected $model = Ingredient::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'name' => Str::title($name),
            'slug' => Str::slug($name),
            'description' => fake()->sentence(),
            'status' => ContentStatus::Published,
        ];
    }
}
