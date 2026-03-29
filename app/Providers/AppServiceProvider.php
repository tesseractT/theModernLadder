<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Factory::guessFactoryNamesUsing(
            fn (string $modelName): string => 'Database\\Factories\\'.class_basename($modelName).'Factory'
        );

        JsonResource::withoutWrapping();

        Model::shouldBeStrict(! $this->app->isProduction());

        RateLimiter::for('api', function (Request $request): Limit {
            return Limit::perMinute((int) config('api.rate_limit_per_minute', 60))
                ->by($request->user()?->getAuthIdentifier() ?? $request->ip());
        });
    }
}
