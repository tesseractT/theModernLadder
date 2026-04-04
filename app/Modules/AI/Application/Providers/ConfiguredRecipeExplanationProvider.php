<?php

namespace App\Modules\AI\Application\Providers;

use App\Modules\AI\Application\Contracts\RecipeExplanationProvider;
use App\Modules\AI\Application\DTO\RecipeExplanationPrompt;
use App\Modules\AI\Application\DTO\RecipeExplanationProviderResponse;
use App\Modules\AI\Application\Exceptions\RecipeExplanationProviderException;
use Illuminate\Contracts\Foundation\Application;

class ConfiguredRecipeExplanationProvider implements RecipeExplanationProvider
{
    public function __construct(
        protected Application $app,
    ) {}

    public function generate(RecipeExplanationPrompt $prompt): RecipeExplanationProviderResponse
    {
        return match (config('ai.provider', 'openai')) {
            'openai' => $this->app->make(OpenAiRecipeExplanationProvider::class)->generate($prompt),
            default => throw new RecipeExplanationProviderException(
                'Unsupported recipe explanation provider configured.',
                ['provider' => config('ai.provider')]
            ),
        };
    }
}
