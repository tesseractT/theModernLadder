<?php

namespace App\Modules\Admin\Application\Services;

use App\Modules\Admin\Domain\Enums\AdminEventStream;
use App\Modules\Admin\Domain\Models\AdminEvent;
use App\Modules\Shared\Application\Support\LogContextSanitizer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

class AdminEventRecorder
{
    public function recordSecurityAudit(
        string $event,
        Request $request,
        array $context = [],
        string|int|null $actorId = null,
    ): void {
        $safeContext = LogContextSanitizer::sanitize($context);
        $targetType = is_string($safeContext['target_type'] ?? null)
            ? $safeContext['target_type']
            : null;
        $targetId = isset($safeContext['target_id']) ? (string) $safeContext['target_id'] : null;

        unset($safeContext['target_type'], $safeContext['target_id']);

        $this->record(
            stream: AdminEventStream::SecurityAudit,
            event: $event,
            actorUserId: $this->normalizeActorId($actorId ?? $request->user()?->getAuthIdentifier()),
            targetType: $targetType,
            targetId: $targetId,
            requestId: $this->normalizeString($request->attributes->get('request_id') ?: $request->header('X-Request-Id')),
            routeName: $request->route()?->getName(),
            metadata: $safeContext,
        );
    }

    public function recordAiExplanationFailure(
        string $event,
        ?string $actorUserId,
        ?string $targetId,
        ?string $requestId,
        ?string $routeName,
        array $metadata = [],
    ): void {
        $this->record(
            stream: AdminEventStream::AiExplanationFailure,
            event: $event,
            actorUserId: $this->normalizeActorId($actorUserId),
            targetType: 'recipe_template',
            targetId: $this->normalizeString($targetId),
            requestId: $this->normalizeString($requestId),
            routeName: $this->normalizeString($routeName),
            metadata: LogContextSanitizer::sanitize($metadata),
        );
    }

    protected function record(
        AdminEventStream $stream,
        string $event,
        ?string $actorUserId,
        ?string $targetType,
        ?string $targetId,
        ?string $requestId,
        ?string $routeName,
        array $metadata = [],
    ): void {
        if (! Schema::hasTable('admin_events')) {
            return;
        }

        try {
            AdminEvent::query()->create([
                'stream' => $stream,
                'event' => $event,
                'actor_user_id' => $actorUserId,
                'target_type' => $targetType,
                'target_id' => $targetId,
                'request_id' => $requestId,
                'route_name' => $routeName,
                'metadata' => $this->filteredMetadata($metadata),
                'occurred_at' => now(),
                'created_at' => now(),
            ]);
        } catch (Throwable $throwable) {
            Log::warning('admin.event.persist_failed', [
                'stream' => $stream->value,
                'event' => $event,
                'exception_class' => $throwable::class,
            ]);
        }
    }

    protected function filteredMetadata(array $metadata): array
    {
        return array_filter(
            $metadata,
            fn (mixed $value): bool => $value !== null && $value !== '' && $value !== []
        );
    }

    protected function normalizeActorId(string|int|null $actorId): ?string
    {
        if ($actorId === null) {
            return null;
        }

        $value = trim((string) $actorId);

        return $value !== '' ? $value : null;
    }

    protected function normalizeString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = trim((string) $value);

        return $normalized !== '' ? $normalized : null;
    }
}
