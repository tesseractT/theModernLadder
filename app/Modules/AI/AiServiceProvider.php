<?php

namespace App\Modules\AI;

use App\Modules\AI\Application\Contracts\RecipeExplanationProvider;
use App\Modules\AI\Application\Providers\ConfiguredRecipeExplanationProvider;
use Illuminate\Support\ServiceProvider;

class AiServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(
            RecipeExplanationProvider::class,
            ConfiguredRecipeExplanationProvider::class
        );
    }
}
