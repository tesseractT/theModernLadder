<?php

namespace Tests\Unit\AI;

use App\Modules\AI\Application\DTO\RecipeExplanationContext;
use App\Modules\AI\Application\Exceptions\InvalidRecipeExplanationException;
use App\Modules\AI\Application\Support\RecipeExplanationOutputValidator;
use Tests\TestCase;

class RecipeExplanationOutputValidatorTest extends TestCase
{
    public function test_it_rejects_unsupported_certainty_language_with_safe_exception_context(): void
    {
        $validator = $this->app->make(RecipeExplanationOutputValidator::class);
        $context = new RecipeExplanationContext(
            requestId: 'request-1',
            userId: 'user-1',
            templateId: 'template-1',
            template: [
                'id' => 'template-1',
                'title' => 'Pantry Smoothie',
            ],
            pantryFit: [],
            ingredients: [],
            steps: [],
            substitutions: [],
            preferences: [
                'dietary_patterns' => [],
            ],
            allowedFollowUpOptions: [
                [
                    'key' => 'same_pantry_new_recipe',
                    'label_hint' => 'Want another idea using the same pantry?',
                ],
            ],
            promptVersion: 'recipe_template_explanation.v1',
            schemaVersion: 'recipe_template_explanation.v1',
        );

        try {
            $validator->validate([
                'headline' => 'A confident match.',
                'why_it_fits' => 'This definitely works every time.',
                'taste_profile' => 'Taste stays bright and fruit-led from the stored template data.',
                'texture_profile' => 'Texture should stay smooth because the template is built as a drink.',
                'substitution_guidance' => [
                    'Use the published swaps already stored for this template.',
                ],
                'quick_takeaways' => [
                    'The pantry fit is grounded in stored ingredients.',
                    'The template remains a quick drink option.',
                ],
                'follow_up_options' => [
                    [
                        'key' => 'same_pantry_new_recipe',
                        'label' => 'Want another idea using the same pantry?',
                    ],
                ],
            ], $context);

            $this->fail('Expected an invalid recipe explanation exception.');
        } catch (InvalidRecipeExplanationException $exception) {
            $this->assertSame('safety_boundary_violation', $exception->context['reason'] ?? null);
            $this->assertSame('unsupported_certainty_language', $exception->context['category'] ?? null);
            $this->assertArrayNotHasKey('fragment', $exception->context);
        }
    }
}
