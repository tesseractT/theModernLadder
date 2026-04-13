<?php

namespace App\Modules\Moderation\Http\Resources;

use App\Modules\Users\Domain\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ModerationActionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'action' => $this->action?->value,
            'from_status' => $this->from_status?->value,
            'to_status' => $this->to_status?->value,
            'reason_code' => $this->reason_code,
            'notes' => $this->notes,
            'request_id' => $this->request_id,
            'actor' => $this->relationLoaded('actor')
                ? $this->actorSummary($this->actor)
                : null,
            'created_at' => $this->created_at?->toIso8601String(),
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
