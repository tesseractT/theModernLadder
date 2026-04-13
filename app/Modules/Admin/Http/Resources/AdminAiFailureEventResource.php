<?php

namespace App\Modules\Admin\Http\Resources;

use App\Modules\Users\Domain\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminAiFailureEventResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $metadata = $this->metadata ?? [];

        return [
            'id' => $this->id,
            'event' => $this->event,
            'request_id' => $this->request_id,
            'route_name' => $this->route_name,
            'actor' => $this->relationLoaded('actor')
                ? $this->actorSummary($this->actor)
                : ['id' => $this->actor_user_id],
            'target' => [
                'type' => $this->target_type,
                'id' => $this->target_id,
            ],
            'provider' => $metadata['provider'] ?? null,
            'model' => $metadata['model'] ?? null,
            'failure_type' => $metadata['failure_type'] ?? null,
            'failure_reason' => $metadata['failure_reason'] ?? null,
            'error_status' => $metadata['error_status'] ?? null,
            'error_code' => $metadata['error_code'] ?? null,
            'fallback_used' => $metadata['fallback_used'] ?? null,
            'prompt_version' => $metadata['prompt_version'] ?? null,
            'schema_version' => $metadata['schema_version'] ?? null,
            'occurred_at' => $this->occurred_at?->toIso8601String(),
        ];
    }

    protected function actorSummary(?User $user): ?array
    {
        if (! $user instanceof User) {
            return null;
        }

        return [
            'id' => $user->id,
            'display_name' => $user->profile?->display_name ?? $user->defaultDisplayName(),
        ];
    }
}
