<?php

use App\Modules\Shared\Application\Support\RequestLogContext;
use App\Modules\Shared\Http\Middleware\AddApiSecurityHeaders;
use App\Modules\Shared\Http\Middleware\AssignRequestId;
use App\Modules\Shared\Http\Middleware\EnsureActiveUser;
use App\Modules\Shared\Http\Middleware\ForceJsonResponse;
use App\Modules\Shared\Http\Middleware\ObserveApiRequests;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

$prefersRedisThrottle = filter_var(env('API_THROTTLE_REDIS', true), FILTER_VALIDATE_BOOL);

$redisThrottleAvailable = match (env('REDIS_CLIENT', 'phpredis')) {
    'phpredis' => extension_loaded('redis') && class_exists('Redis'),
    'predis' => class_exists('Predis\\Client'),
    default => false,
};

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) use ($prefersRedisThrottle, $redisThrottleAvailable): void {
        $middleware->alias([
            'active.user' => EnsureActiveUser::class,
        ]);

        $middleware->throttleApi(
            // Fall back to Laravel's standard throttle middleware when the active PHP runtime
            // cannot actually boot the configured Redis client.
            redis: $prefersRedisThrottle && $redisThrottleAvailable,
        );
        $middleware->api(prepend: [
            AddApiSecurityHeaders::class,
            AssignRequestId::class,
            ForceJsonResponse::class,
            ObserveApiRequests::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $forbiddenResponse = fn () => response()->json([
            'message' => 'You do not have permission to perform this action.',
            'code' => 'forbidden',
        ], 403);

        $exceptions->report(function (Throwable $exception): void {
            if (! app()->bound('request')) {
                return;
            }

            $request = request();

            if (! $request instanceof Request || ! $request->is('api/*')) {
                return;
            }

            if ($request->attributes->get('api_request_exception_logged') === true) {
                return;
            }

            $request->attributes->set('api_request_exception_logged', true);

            $startedAt = $request->attributes->get('request_started_at_hrtime');
            $durationMs = is_int($startedAt)
                ? (int) round((hrtime(true) - $startedAt) / 1_000_000)
                : 0;

            Log::error('api.request.exception', RequestLogContext::forException(
                request: $request,
                throwable: $exception,
                durationMs: $durationMs,
            ));
        });

        $exceptions->render(function (AuthenticationException $exception, Request $request): ?Response {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'message' => 'Authentication required.',
                'code' => 'unauthenticated',
            ], 401);
        });

        $exceptions->render(function (AuthorizationException $exception, Request $request): ?Response {
            if (! $request->is('api/*')) {
                return null;
            }

            return $forbiddenResponse();
        });

        $exceptions->render(function (AccessDeniedHttpException $exception, Request $request) use ($forbiddenResponse): ?Response {
            if (! $request->is('api/*')) {
                return null;
            }

            return $forbiddenResponse();
        });
    })->create();
