<?php

namespace App\Modules\AI\Application\Contracts;

use App\Modules\AI\Application\DTO\RecipeExplanationPrompt;
use App\Modules\AI\Application\DTO\RecipeExplanationProviderResponse;

interface RecipeExplanationProvider
{
    public function generate(RecipeExplanationPrompt $prompt): RecipeExplanationProviderResponse;
}
