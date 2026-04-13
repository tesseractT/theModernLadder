<?php

namespace App\Modules\Admin\Http\Resources;

use App\Modules\Contributions\Http\Resources\ContributionSubjectResource;
use App\Modules\Users\Domain\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class AdminModerationActionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'action' => $this->action?->value,
            'from_status' => $this->from_status?->value,
            'to_status' => $this->to_status?->value,
            'reason_code' => $this->reason_code,
            'notes_summary' => filled($this->notes)
                ? Str::limit((string) $this->notes, 160)
                : null,
            'request_id' => $this->request_id,
            'actor' => $this->relationLoaded('actor')
                ? $this->actorSummary($this->actor)
                : null,
            'target' => $this->relationLoaded('contribution') && $this->contribution !== null
                ? [
                    'type' => 'contribution',
                    'id' => $this->contribution->id,
                    'status' => $this->contribution->status?->value,
                    'contribution_type' => $this->contribution->type?->value,
                    'subject' => $this->contribution->relationLoaded('subject') && $this->contribution->subject !== null
                        ? ContributionSubjectResource::make($this->contribution->subject)->resolve($request)
                        : null,
                ]
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
