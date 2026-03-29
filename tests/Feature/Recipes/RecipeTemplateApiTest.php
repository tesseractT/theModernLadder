<?php

namespace Tests\Feature\Recipes;

use App\Modules\Recipes\Domain\Models\RecipeTemplate;
use App\Modules\Shared\Domain\Enums\ContentStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecipeTemplateApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_lists_only_published_recipe_templates(): void
    {
        RecipeTemplate::factory()->create([
            'title' => 'Herby Tomato Pasta',
            'slug' => 'herby-tomato-pasta',
        ]);

        RecipeTemplate::factory()->create([
            'title' => 'Hidden Draft',
            'slug' => 'hidden-draft',
            'status' => ContentStatus::Draft,
        ]);

        $response = $this->getJson('/api/v1/recipe-templates?search=tomato');

        $response
            ->assertOk()
            ->assertJsonPath('data.0.slug', 'herby-tomato-pasta')
            ->assertJsonMissing(['slug' => 'hidden-draft']);
    }

    public function test_it_shows_a_single_published_recipe_template(): void
    {
        RecipeTemplate::factory()->create([
            'title' => 'Roasted Vegetables',
            'slug' => 'roasted-vegetables',
        ]);

        $response = $this->getJson('/api/v1/recipe-templates/roasted-vegetables');

        $response
            ->assertOk()
            ->assertJsonPath('slug', 'roasted-vegetables');
    }

    public function test_it_returns_not_found_for_unpublished_recipe_templates(): void
    {
        RecipeTemplate::factory()->create([
            'slug' => 'private-recipe',
            'status' => ContentStatus::Archived,
        ]);

        $this->getJson('/api/v1/recipe-templates/private-recipe')
            ->assertNotFound();
    }
}
