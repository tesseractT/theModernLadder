<?php

namespace App\Modules\Shared\Application\Support;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class RequestLogContext
{
    public static function sharedKeys(): array
    {
        return [
            'request_id',
            'request_method',
            'request_path',
            'route_name',
            'route_action',
            'user_id',
        ];
    }

    public static function forRequest(Request $request, ?string $requestId = null): array
    {
        $route = $request->route();

        return array_filter([
            'request_id' => $requestId ?? self::requestId($request),
            'request_method' => $request->method(),
            'request_path' => '/'.ltrim($request->path(), '/'),
            'route_name' => $route?->getName(),
            'route_action' => $route?->getActionName(),
            'user_id' => $request->user()?->getAuthIdentifier(),
        ], fn (mixed $value): bool => $value !== null && $value !== '');
    }

    public static function forResponse(
        Request $request,
        Response $response,
        int $durationMs,
        ?string $requestId = null,
    ): array {
        return [
            ...self::forRequest($request, $requestId),
            'status_code' => $response->getStatusCode(),
            'duration_ms' => $durationMs,
        ];
    }

    public static function forException(
        Request $request,
        Throwable $throwable,
        int $durationMs,
        ?string $requestId = null,
    ): array {
        return [
            ...self::forRequest($request, $requestId),
            'duration_ms' => $durationMs,
            'exception_class' => $throwable::class,
        ];
    }

    protected static function requestId(Request $request): ?string
    {
        $requestId = $request->attributes->get('request_id') ?: $request->header('X-Request-Id');

        return is_string($requestId) && $requestId !== '' ? $requestId : null;
    }
}
