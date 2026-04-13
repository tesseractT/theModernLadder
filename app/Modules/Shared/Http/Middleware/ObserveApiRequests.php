<?php

namespace App\Modules\Shared\Http\Middleware;

use App\Modules\Shared\Application\Support\RequestLogContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ObserveApiRequests
{
    public function handle(Request $request, Closure $next): Response
    {
        $startedAt = hrtime(true);
        $request->attributes->set('request_started_at_hrtime', $startedAt);
        $response = $next($request);

        $durationMs = $this->durationMs($startedAt);
        $exceptionAlreadyLogged = $request->attributes->get('api_request_exception_logged') === true;

        if ($response->getStatusCode() >= 500 && ! $exceptionAlreadyLogged) {
            Log::error('api.request.failed', RequestLogContext::forResponse(
                request: $request,
                response: $response,
                durationMs: $durationMs,
            ));
        } elseif ($this->isSlowRequest($durationMs)) {
            Log::warning('api.request.slow', RequestLogContext::forResponse(
                request: $request,
                response: $response,
                durationMs: $durationMs,
            ));
        }

        return $response;
    }

    protected function durationMs(int $startedAt): int
    {
        return (int) round((hrtime(true) - $startedAt) / 1_000_000);
    }

    protected function isSlowRequest(int $durationMs): bool
    {
        $thresholdMs = (int) config('logging.slow_request_threshold_ms', 1000);

        return $thresholdMs > 0 && $durationMs >= $thresholdMs;
    }
}
