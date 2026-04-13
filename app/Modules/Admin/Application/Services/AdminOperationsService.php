<?php

namespace App\Modules\Admin\Application\Services;

use App\Modules\Admin\Domain\Models\AdminEvent;
use App\Modules\Contributions\Domain\Enums\ContributionStatus;
use App\Modules\Contributions\Domain\Enums\ContributionSubjectType;
use App\Modules\Contributions\Domain\Models\Contribution;
use App\Modules\Ingredients\Domain\Models\Ingredient;
use App\Modules\Moderation\Domain\Enums\ModerationActionType;
use App\Modules\Moderation\Domain\Models\ModerationAction;
use App\Modules\Recipes\Domain\Models\RecipeTemplate;
use App\Modules\Users\Domain\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class AdminOperationsService
{
    protected const SUSPICIOUS_ACTIVITY_LOOKBACK_DAYS = 7;

    protected const REPORT_VOLUME_THRESHOLD = 3;

    protected const MODERATION_CHURN_THRESHOLD = 2;

    protected const AI_FAILURE_THRESHOLD = 2;

    public function paginateFlaggedContributions(
        int $perPage,
        ?string $reasonCode = null,
        ?ContributionSubjectType $subjectType = null,
    ): LengthAwarePaginator {
        return Contribution::query()
            ->where('status', ContributionStatus::Flagged->value)
            ->when($reasonCode, function (Builder $query) use ($reasonCode): void {
                $query->whereHas('moderationCases', function (Builder $caseQuery) use ($reasonCode): void {
                    $caseQuery->active()->where('reason_code', $reasonCode);
                });
            })
            ->when($subjectType, function (Builder $query) use ($subjectType): void {
                $query->where('subject_type', $this->subjectClass($subjectType));
            })
            ->with([
                'subject',
                'submitter.profile',
                'reviewer.profile',
                'moderationCases' => fn ($query) => $query
                    ->active()
                    ->with(['reporter.profile', 'assignee.profile'])
                    ->withCount([
                        'actions as reports_count' => fn (Builder $actionQuery): Builder => $actionQuery
                            ->where('action', ModerationActionType::Reported->value),
                    ])
                    ->latest('created_at'),
                'moderationActions' => fn ($query) => $query
                    ->with(['actor.profile'])
                    ->latest('created_at'),
            ])
            ->withCount([
                'moderationActions as reports_count' => fn (Builder $query): Builder => $query
                    ->where('action', ModerationActionType::Reported->value),
            ])
            ->orderByDesc('updated_at')
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function paginateModerationActions(
        int $perPage,
        ?ModerationActionType $action = null,
        ?string $actorUserId = null,
    ): LengthAwarePaginator {
        return ModerationAction::query()
            ->with([
                'actor.profile',
                'contribution.subject',
            ])
            ->when($action, function (Builder $query) use ($action): void {
                $query->where('action', $action->value);
            })
            ->when($actorUserId, function (Builder $query) use ($actorUserId): void {
                $query->where('actor_user_id', $actorUserId);
            })
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function paginateAuditEvents(
        int $perPage,
        ?string $event = null,
        ?string $actorUserId = null,
    ): LengthAwarePaginator {
        return AdminEvent::query()
            ->securityAudit()
            ->with('actor.profile')
            ->when($event, function (Builder $query) use ($event): void {
                $query->where('event', $event);
            })
            ->when($actorUserId, function (Builder $query) use ($actorUserId): void {
                $query->where('actor_user_id', $actorUserId);
            })
            ->orderByDesc('occurred_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function paginateAiFailures(int $perPage, ?string $templateId = null): LengthAwarePaginator
    {
        return AdminEvent::query()
            ->aiExplanationFailures()
            ->with('actor.profile')
            ->when($templateId, function (Builder $query) use ($templateId): void {
                $query->where('target_id', $templateId);
            })
            ->orderByDesc('occurred_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function suspiciousActivitySummary(): array
    {
        $asOf = now();
        $lookbackStart = $asOf->copy()->subDays(self::SUSPICIOUS_ACTIVITY_LOOKBACK_DAYS);

        return [
            'as_of' => $asOf->toIso8601String(),
            'lookback_days' => self::SUSPICIOUS_ACTIVITY_LOOKBACK_DAYS,
            'signals' => [
                'high_report_volume_users' => $this->highReportVolumeUsers($lookbackStart),
                'contribution_churn' => $this->contributionChurn($lookbackStart),
                'repeated_ai_failures' => $this->repeatedAiFailures($lookbackStart),
            ],
        ];
    }

    protected function highReportVolumeUsers(Carbon $lookbackStart): array
    {
        $rows = ModerationAction::query()
            ->selectRaw('actor_user_id, count(*) as reports_count, max(created_at) as last_reported_at')
            ->where('action', ModerationActionType::Reported->value)
            ->whereNotNull('actor_user_id')
            ->where('created_at', '>=', $lookbackStart)
            ->groupBy('actor_user_id')
            ->havingRaw('count(*) >= ?', [self::REPORT_VOLUME_THRESHOLD])
            ->orderByDesc('reports_count')
            ->limit(10)
            ->get();

        $users = User::query()
            ->with('profile')
            ->whereIn('id', $rows->pluck('actor_user_id')->filter()->all())
            ->get()
            ->keyBy('id');

        return $rows->map(function (object $row) use ($users): array {
            $user = $users->get($row->actor_user_id);

            return [
                'user' => $this->actorSummary($user, $row->actor_user_id),
                'reports_count' => (int) $row->reports_count,
                'last_reported_at' => $this->isoTimestamp($row->last_reported_at),
            ];
        })->values()->all();
    }

    protected function contributionChurn(Carbon $lookbackStart): array
    {
        $rows = ModerationAction::query()
            ->selectRaw('contribution_id, count(*) as action_count, count(distinct to_status) as distinct_target_statuses, max(created_at) as last_action_at')
            ->whereIn('action', ModerationActionType::reviewValues())
            ->where('created_at', '>=', $lookbackStart)
            ->groupBy('contribution_id')
            ->havingRaw('count(*) >= ?', [self::MODERATION_CHURN_THRESHOLD])
            ->havingRaw('count(distinct to_status) >= ?', [self::MODERATION_CHURN_THRESHOLD])
            ->orderByDesc('action_count')
            ->limit(10)
            ->get();

        $contributions = Contribution::query()
            ->with('subject')
            ->whereIn('id', $rows->pluck('contribution_id')->filter()->all())
            ->get()
            ->keyBy('id');

        return $rows->map(function (object $row) use ($contributions): array {
            /** @var Contribution|null $contribution */
            $contribution = $contributions->get($row->contribution_id);

            return [
                'contribution' => $this->contributionSummary($contribution, $row->contribution_id),
                'action_count' => (int) $row->action_count,
                'distinct_target_statuses' => (int) $row->distinct_target_statuses,
                'last_action_at' => $this->isoTimestamp($row->last_action_at),
            ];
        })->values()->all();
    }

    protected function repeatedAiFailures(Carbon $lookbackStart): array
    {
        $rows = AdminEvent::query()
            ->aiExplanationFailures()
            ->selectRaw('target_id, count(*) as failures_count, max(occurred_at) as last_occurred_at')
            ->where('target_type', 'recipe_template')
            ->whereNotNull('target_id')
            ->where('occurred_at', '>=', $lookbackStart)
            ->groupBy('target_id')
            ->havingRaw('count(*) >= ?', [self::AI_FAILURE_THRESHOLD])
            ->orderByDesc('failures_count')
            ->limit(10)
            ->get();

        $templates = RecipeTemplate::query()
            ->whereIn('id', $rows->pluck('target_id')->filter()->all())
            ->get()
            ->keyBy('id');

        return $rows->map(function (object $row) use ($templates): array {
            /** @var RecipeTemplate|null $template */
            $template = $templates->get($row->target_id);

            return [
                'template' => [
                    'id' => $row->target_id,
                    'title' => $template?->title,
                    'slug' => $template?->slug,
                ],
                'failures_count' => (int) $row->failures_count,
                'last_occurred_at' => $this->isoTimestamp($row->last_occurred_at),
            ];
        })->values()->all();
    }

    protected function actorSummary(?User $user, ?string $fallbackId = null): ?array
    {
        if ($user === null && $fallbackId === null) {
            return null;
        }

        return [
            'id' => $user?->id ?? $fallbackId,
            'display_name' => $user?->profile?->display_name ?? $user?->defaultDisplayName(),
            'role' => $user?->roleOrDefault()->value,
        ];
    }

    protected function contributionSummary(?Contribution $contribution, ?string $fallbackId = null): ?array
    {
        if ($contribution === null && $fallbackId === null) {
            return null;
        }

        return [
            'id' => $contribution?->id ?? $fallbackId,
            'type' => $contribution?->type?->value,
            'status' => $contribution?->status?->value,
            'subject' => $contribution?->relationLoaded('subject') && $contribution->subject !== null
                ? $this->subjectSummary($contribution->subject)
                : null,
        ];
    }

    protected function subjectClass(ContributionSubjectType $subjectType): string
    {
        return match ($subjectType) {
            ContributionSubjectType::Ingredient => Ingredient::class,
            ContributionSubjectType::RecipeTemplate => RecipeTemplate::class,
        };
    }

    protected function isoTimestamp(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return Carbon::parse((string) $value)->toIso8601String();
    }

    protected function subjectSummary(mixed $subject): ?array
    {
        return match (true) {
            $subject instanceof Ingredient => [
                'type' => ContributionSubjectType::Ingredient->value,
                'id' => $subject->id,
                'name' => $subject->name,
                'slug' => $subject->slug,
                'description' => $subject->description,
            ],
            $subject instanceof RecipeTemplate => [
                'type' => ContributionSubjectType::RecipeTemplate->value,
                'id' => $subject->id,
                'title' => $subject->title,
                'slug' => $subject->slug,
                'summary' => $subject->summary,
            ],
            default => null,
        };
    }
}
