<?php

namespace App\Modules\Admin\Http\Resources;

use App\Modules\Contributions\Http\Resources\ContributionSubjectResource;
use App\Modules\Moderation\Domain\Models\ModerationAction;
use App\Modules\Moderation\Domain\Models\ModerationCase;
use App\Modules\Users\Domain\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminFlaggedContributionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $activeCase = $this->relationLoaded('moderationCases')
            ? $this->moderationCases->first(fn (ModerationCase $case): bool => $case->status?->isActive() ?? false)
            : null;
        $latestAction = $this->relationLoaded('moderationActions')
            ? $this->moderationActions->first()
            : null;

        return [
            'id' => $this->id,
            'type' => $this->type?->value,
            'status' => $this->status?->value,
            'subject' => $this->relationLoaded('subject') && $this->subject !== null
                ? ContributionSubjectResource::make($this->subject)->resolve($request)
                : null,
            'submitted_by' => $this->relationLoaded('submitter')
                ? $this->actorSummary($this->submitter)
                : null,
            'reports_count' => isset($this->reports_count) ? (int) $this->reports_count : 0,
            'flagged_context' => [
                'active_case' => $activeCase !== null
                    ? AdminModerationCaseSummaryResource::make($activeCase)->resolve($request)
                    : null,
                'latest_action' => $latestAction instanceof ModerationAction
                    ? AdminModerationActionResource::make($latestAction)->resolve($request)
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
