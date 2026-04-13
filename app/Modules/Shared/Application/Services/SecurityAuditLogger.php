<?php

namespace App\Modules\Shared\Application\Services;

use App\Modules\Admin\Application\Services\AdminEventRecorder;
use App\Modules\Shared\Application\Support\LogContextSanitizer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SecurityAuditLogger
{
    public function __construct(
        protected AdminEventRecorder $adminEventRecorder,
    ) {}

    public function log(
        string $event,
        Request $request,
        array $context = [],
        string|int|null $actorId = null,
    ): void {
        $safeContext = LogContextSanitizer::sanitize($context);

        unset($safeContext['event'], $safeContext['actor_id'], $safeContext['request_id']);

        $this->adminEventRecorder->recordSecurityAudit(
            event: $event,
            request: $request,
            context: $safeContext,
            actorId: $actorId,
        );

        Log::info('security.audit', array_filter([
            'event' => $event,
            'actor_id' => $actorId ?? $request->user()?->getAuthIdentifier(),
            'request_id' => $request->attributes->get('request_id') ?: $request->header('X-Request-Id'),
            ...$safeContext,
        ], fn (mixed $value): bool => $value !== null));
    }
}
