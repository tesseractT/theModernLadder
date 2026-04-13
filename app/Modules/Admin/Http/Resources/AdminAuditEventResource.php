<?php

namespace App\Modules\Admin\Http\Resources;

use App\Modules\Users\Domain\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminAuditEventResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'stream' => $this->stream?->value,
            'event' => $this->event,
            'actor' => $this->relationLoaded('actor')
                ? $this->actorSummary($this->actor)
                : ['id' => $this->actor_user_id],
            'target_type' => $this->target_type,
            'target_id' => $this->target_id,
            'request_id' => $this->request_id,
            'route_name' => $this->route_name,
            'metadata' => $this->metadata ?? [],
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
            'role' => $user->roleOrDefault()->value,
        ];
    }
}
