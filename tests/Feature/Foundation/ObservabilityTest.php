<?php

namespace Tests\Feature\Foundation;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use RuntimeException;
use Tests\TestCase;

class ObservabilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware('api')
            ->prefix('api/'.config('api.version'))
            ->group(function (): void {
                Route::get('/_test/observability/log-context', function () {
                    Log::info('observability.test');

                    return response()->json([
                        'ok' => true,
                    ]);
                })->name('tests.observability.log-context');

                Route::get('/_test/observability/slow', function () {
                    usleep(5_000);

                    return response()->json([
                        'ok' => true,
                    ]);
                })->name('tests.observability.slow');

                Route::get('/_test/observability/boom', function () {
                    throw new RuntimeException('Observability test exception.');
                })->name('tests.observability.boom');
            });
    }

    public function test_request_scoped_log_context_is_shared_with_api_logs(): void
    {
        $requestId = 'obs-log-context-123';

        Event::fake([
            MessageLogged::class,
        ]);

        $this->withHeaders([
            'X-Request-Id' => $requestId,
        ])->getJson('/api/v1/_test/observability/log-context')
            ->assertOk()
            ->assertHeader('X-Request-Id', $requestId);

        Event::assertDispatched(MessageLogged::class, function (MessageLogged $event) use ($requestId): bool {
            return $event->level === 'info'
                && $event->message === 'observability.test'
                && ($event->context['request_id'] ?? null) === $requestId
                && ($event->context['request_method'] ?? null) === 'GET'
                && ($event->context['request_path'] ?? null) === '/api/v1/_test/observability/log-context'
                && ($event->context['route_name'] ?? null) === 'tests.observability.log-context'
                && filled($event->context['route_action'] ?? null);
        });
    }

    public function test_slow_api_requests_are_logged_with_structured_context(): void
    {
        config()->set('logging.slow_request_threshold_ms', 1);

        Event::fake([
            MessageLogged::class,
        ]);

        $this->getJson('/api/v1/_test/observability/slow')
            ->assertOk();

        Event::assertDispatched(MessageLogged::class, function (MessageLogged $event): bool {
            return $event->level === 'warning'
                && $event->message === 'api.request.slow'
                && ($event->context['request_method'] ?? null) === 'GET'
                && ($event->context['request_path'] ?? null) === '/api/v1/_test/observability/slow'
                && ($event->context['route_name'] ?? null) === 'tests.observability.slow'
                && ($event->context['status_code'] ?? null) === 200
                && is_int($event->context['duration_ms'] ?? null)
                && ($event->context['duration_ms'] ?? 0) >= 1;
        });
    }

    public function test_unhandled_api_exceptions_are_logged_with_request_context(): void
    {
        $requestId = 'obs-exception-123';

        Event::fake([
            MessageLogged::class,
        ]);

        $this->withHeaders([
            'X-Request-Id' => $requestId,
        ])->getJson('/api/v1/_test/observability/boom')
            ->assertStatus(500)
            ->assertHeader('X-Request-Id', $requestId);

        Event::assertDispatched(MessageLogged::class, function (MessageLogged $event) use ($requestId): bool {
            return $event->level === 'error'
                && $event->message === 'api.request.exception'
                && ($event->context['request_id'] ?? null) === $requestId
                && ($event->context['request_method'] ?? null) === 'GET'
                && ($event->context['request_path'] ?? null) === '/api/v1/_test/observability/boom'
                && ($event->context['route_name'] ?? null) === 'tests.observability.boom'
                && ($event->context['exception_class'] ?? null) === RuntimeException::class
                && is_int($event->context['duration_ms'] ?? null);
        });
    }

    public function test_api_health_endpoint_returns_a_json_readiness_payload(): void
    {
        $response = $this->getJson('/api/v1/health')
            ->assertOk()
            ->assertHeader('X-Request-Id')
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('checks.app.status', 'ok')
            ->assertJsonPath('checks.database.status', 'ok')
            ->assertJsonPath('meta.api_version', 'v1');

        $this->assertSame(
            $response->headers->get('X-Request-Id'),
            $response->json('meta.request_id')
        );
    }
}
