<?php

namespace Tests\Unit\AI;

use App\Modules\AI\Application\DTO\RecipeExplanationPrompt;
use App\Modules\AI\Application\Exceptions\RecipeExplanationProviderException;
use App\Modules\AI\Application\Providers\OpenAiRecipeExplanationProvider;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OpenAiRecipeExplanationProviderTest extends TestCase
{
    public function test_it_posts_a_structured_output_request_to_openai_and_parses_json(): void
    {
        config()->set('services.openai.key', 'test-key');
        config()->set('ai.providers.openai.base_url', 'https://api.openai.com/v1');
        config()->set('ai.providers.openai.model', 'gpt-5-mini');
        config()->set('ai.providers.openai.store', false);

        Http::fake(function (Request $request) {
            $this->assertSame('https://api.openai.com/v1/responses', $request->url());

            $payload = json_decode($request->body(), true, 512, JSON_THROW_ON_ERROR);

            $this->assertSame('gpt-5-mini', $payload['model']);
            $this->assertFalse($payload['store']);
            $this->assertSame('json_schema', $payload['text']['format']['type']);
            $this->assertSame('recipe_template_explanation_v1', $payload['text']['format']['name']);

            return Http::response([
                'output' => [
                    [
                        'type' => 'message',
                        'content' => [
                            [
                                'type' => 'output_text',
                                'text' => json_encode([
                                    'headline' => 'A grounded explanation.',
                                    'why_it_fits' => 'The pantry fit is already known from structured data.',
                                    'taste_profile' => 'Taste follows the grounded ingredient descriptions only.',
                                    'texture_profile' => 'Texture stays consistent with the stored template steps.',
                                    'substitution_guidance' => [],
                                    'quick_takeaways' => [
                                        'Grounded data was preserved.',
                                        'No raw provider text was forwarded.',
                                    ],
                                    'follow_up_options' => [
                                        ['key' => 'same_pantry_new_recipe', 'label' => 'Want another idea using the same pantry?'],
                                    ],
                                ], JSON_THROW_ON_ERROR),
                            ],
                        ],
                    ],
                ],
            ], 200);
        });

        $provider = $this->app->make(OpenAiRecipeExplanationProvider::class);

        $response = $provider->generate(new RecipeExplanationPrompt(
            instructions: 'Use grounded data only.',
            input: '{"template":{"title":"Example"}}',
            schemaName: 'recipe_template_explanation_v1',
            schema: [
                'type' => 'object',
                'properties' => [],
                'required' => [],
                'additionalProperties' => false,
            ],
        ));

        $this->assertSame('openai', $response->provider);
        $this->assertSame('gpt-5-mini', $response->model);
        $this->assertSame('A grounded explanation.', $response->payload['headline']);
    }

    public function test_it_throws_when_openai_returns_malformed_json_output(): void
    {
        config()->set('services.openai.key', 'test-key');

        Http::fake([
            'https://api.openai.com/v1/responses' => Http::response([
                'output' => [
                    [
                        'type' => 'message',
                        'content' => [
                            [
                                'type' => 'output_text',
                                'text' => '{not-json',
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $provider = $this->app->make(OpenAiRecipeExplanationProvider::class);

        $this->expectException(RecipeExplanationProviderException::class);
        $this->expectExceptionMessage('Recipe explanation provider returned malformed JSON.');

        $provider->generate(new RecipeExplanationPrompt(
            instructions: 'Use grounded data only.',
            input: '{"template":{"title":"Example"}}',
            schemaName: 'recipe_template_explanation_v1',
            schema: [
                'type' => 'object',
                'properties' => [],
                'required' => [],
                'additionalProperties' => false,
            ],
        ));
    }
}
