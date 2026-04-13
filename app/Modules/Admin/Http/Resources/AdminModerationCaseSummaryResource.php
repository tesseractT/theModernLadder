<?php

namespace App\Modules\Admin\Http\Resources;

use App\Modules\Users\Domain\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminModerationCaseSummaryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status?->value,
            'reason_code' => $this->reason_code,
            'notes' => $this->notes,
            'reports_count' => isset($this->reports_count) ? (int) $this->reports_count : 0,
            'reported_by' => $this->relationLoaded('reporter')
                ? $this->actorSummary($this->reporter)
                : null,
            'assigned_to' => $this->relationLoaded('assignee')
                ? $this->actorSummary($this->assignee)
                : null,
            'created_at' => $this->created_at?->toIso8601String(),
            'resolved_at' => $this->resolved_at?->toIso8601String(),
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
