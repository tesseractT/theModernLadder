<?php

namespace Tests\Unit\Shared;

use App\Modules\Shared\Application\Services\ApiHealthService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Tests\TestCase;

class ApiHealthServiceTest extends TestCase
{
    public function test_it_returns_unavailable_when_the_database_readiness_check_fails(): void
    {
        Log::spy();

        DB::shouldReceive('connection->select')
            ->once()
            ->andThrow(new RuntimeException('Database unavailable.'));

        $result = app(ApiHealthService::class)->readiness();

        $this->assertSame('unavailable', $result['status']);
        $this->assertSame(503, $result['status_code']);
        $this->assertSame('ok', $result['checks']['app']['status']);
        $this->assertSame('down', $result['checks']['database']['status']);

        Log::shouldHaveReceived('warning')
            ->withArgs(function (string $message, array $context): bool {
                return $message === 'health.database.failed'
                    && ($context['database_connection'] ?? null) === config('database.default')
                    && ($context['exception_class'] ?? null) === RuntimeException::class;
            })
            ->once();
    }
}
