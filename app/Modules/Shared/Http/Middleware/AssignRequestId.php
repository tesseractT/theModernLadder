<?php

namespace App\Modules\Shared\Http\Middleware;

use App\Modules\Shared\Application\Support\RequestLogContext;
use Closure;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class AssignRequestId
{
    public function handle(Request $request, Closure $next): Response
    {
        $requestId = $this->resolveRequestId($request);

        $request->attributes->set('request_id', $requestId);
        $request->headers->set('X-Request-Id', $requestId);

        $this->replaceRequestLogContext($request, $requestId);

        try {
            $response = $next($request);
        } catch (Throwable $throwable) {
            $this->replaceRequestLogContext($request, $requestId);

            $handler = app(ExceptionHandler::class);

            $handler->report($throwable);

            $response = $handler->render($request, $throwable);
        }

        $this->replaceRequestLogContext($request, $requestId);
        $response->headers->set('X-Request-Id', $requestId);

        return $response;
    }

    public function terminate(Request $request, Response $response): void
    {
        Log::withoutContext(RequestLogContext::sharedKeys());
    }

    protected function resolveRequestId(Request $request): string
    {
        $requestId = trim((string) $request->header('X-Request-Id', ''));

        if ($requestId !== '' && preg_match('/\A[a-zA-Z0-9][a-zA-Z0-9._-]{0,127}\z/', $requestId) === 1) {
            return $requestId;
        }

        return (string) Str::uuid();
    }

    protected function replaceRequestLogContext(Request $request, string $requestId): void
    {
        Log::withoutContext(RequestLogContext::sharedKeys());
        Log::shareContext(RequestLogContext::forRequest($request, $requestId));
    }
}
