<?php

namespace App\Modules\Contributions\Http\Resources;

use App\Modules\Moderation\Domain\Models\ModerationCase;
use App\Modules\Moderation\Http\Resources\ModerationCaseResource;
use App\Modules\Users\Domain\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContributionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $activeCase = $this->relationLoaded('moderationCases')
            ? $this->moderationCases->first(fn (ModerationCase $case): bool => $case->status?->isActive() ?? false)
            : null;

        return [
            'id' => $this->id,
            'type' => $this->type?->value,
            'action' => $this->action?->value,
            'status' => $this->status?->value,
            'subject' => $this->relationLoaded('subject') && $this->subject !== null
                ? ContributionSubjectResource::make($this->subject)->resolve($request)
                : null,
            'payload' => $this->payload ?? [],
            'submitted_by' => $this->relationLoaded('submitter')
                ? $this->actorSummary($this->submitter)
                : null,
            'reviewed_by' => $this->relationLoaded('reviewer')
                ? $this->actorSummary($this->reviewer)
                : null,
            'moderation' => [
                'latest_note' => $this->review_notes,
                'reviewed_at' => $this->reviewed_at?->toIso8601String(),
                'reports_count' => isset($this->reports_count) ? (int) $this->reports_count : 0,
                'active_case' => $activeCase !== null
                    ? ModerationCaseResource::make($activeCase)->resolve($request)
                    : null,
            ],
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
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
