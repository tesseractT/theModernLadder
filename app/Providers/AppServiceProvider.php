<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
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

        RateLimiter::for('auth.register', function (Request $request): Limit {
            return $this->routeRateLimit(
                $request,
                'ip:'.($request->ip() ?: 'unknown'),
                (int) config('api.route_rate_limits.auth.register.per_minute', 5),
            );
        });

        RateLimiter::for('auth.login', function (Request $request): Limit {
            return $this->routeRateLimit(
                $request,
                'ip:'.($request->ip() ?: 'unknown'),
                (int) config('api.route_rate_limits.auth.login.per_minute', 10),
            );
        });

        RateLimiter::for('auth.logout', function (Request $request): Limit {
            return $this->routeRateLimit(
                $request,
                $this->authenticatedRateLimitKey($request),
                (int) config('api.route_rate_limits.auth.logout.per_minute', 30),
            );
        });

        RateLimiter::for('recipes.explanation', function (Request $request): Limit {
            return $this->routeRateLimit(
                $request,
                $this->authenticatedRateLimitKey($request),
                (int) config('api.route_rate_limits.recipes.explanation.per_minute', 5),
            );
        });
    }

    protected function routeRateLimit(Request $request, string $key, int $maxAttempts): Limit
    {
        return Limit::perMinute(max(1, $maxAttempts))
            ->by($key)
            ->response(fn (Request $request, array $headers): JsonResponse => $this->tooManyRequestsResponse($request, $headers));
    }

    protected function authenticatedRateLimitKey(Request $request): string
    {
        $identifier = $request->user()?->getAuthIdentifier();

        if ($identifier !== null) {
            return 'user:'.$identifier;
        }

        return 'ip:'.($request->ip() ?: 'unknown');
    }

    protected function tooManyRequestsResponse(Request $request, array $headers): JsonResponse
    {
        $requestId = (string) $request->attributes->get('request_id', '');

        if ($requestId !== '') {
            $headers['X-Request-Id'] = $requestId;
        }

        return response()->json([
            'message' => 'Too many requests. Please try again later.',
            'code' => 'too_many_requests',
            'retry_after_seconds' => max(1, (int) ($headers['Retry-After'] ?? 60)),
        ], 429, $headers);
    }
}
