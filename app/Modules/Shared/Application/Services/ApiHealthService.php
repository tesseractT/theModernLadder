<?php

namespace App\Modules\Shared\Application\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class ApiHealthService
{
    public function readiness(): array
    {
        $checks = [
            'app' => [
                'status' => 'ok',
            ],
        ];

        $status = 'ok';
        $statusCode = 200;

        try {
            DB::connection()->select('select 1');

            $checks['database'] = [
                'status' => 'ok',
            ];
        } catch (Throwable $throwable) {
            $status = 'unavailable';
            $statusCode = 503;

            $checks['database'] = [
                'status' => 'down',
            ];

            Log::warning('health.database.failed', [
                'database_connection' => config('database.default'),
                'exception_class' => $throwable::class,
            ]);
        }

        return [
            'status' => $status,
            'status_code' => $statusCode,
            'checks' => $checks,
        ];
    }
}
