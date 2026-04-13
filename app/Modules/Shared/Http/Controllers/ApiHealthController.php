<?php

namespace App\Modules\Shared\Http\Controllers;

use App\Modules\Shared\Application\Services\ApiHealthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiHealthController extends ApiController
{
    public function __invoke(Request $request, ApiHealthService $healthService): JsonResponse
    {
        $result = $healthService->readiness();

        return $this->respond([
            'status' => $result['status'],
            'checks' => $result['checks'],
            'meta' => [
                'api_version' => config('api.version'),
                'request_id' => (string) ($request->attributes->get('request_id') ?: $request->header('X-Request-Id')),
                'checked_at' => now()->toIso8601String(),
            ],
        ], $result['status_code']);
    }
}
