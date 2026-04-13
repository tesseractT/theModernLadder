<?php

namespace App\Modules\Moderation\Application\Services;

use App\Modules\Contributions\Domain\Enums\ContributionStatus;
use App\Modules\Contributions\Domain\Enums\ContributionSubjectType;
use App\Modules\Contributions\Domain\Models\Contribution;
use App\Modules\Moderation\Application\DTO\ModerateContributionData;
use App\Modules\Moderation\Application\DTO\ReportContributionData;
use App\Modules\Moderation\Domain\Enums\ModerationActionType;
use App\Modules\Moderation\Domain\Enums\ModerationCaseStatus;
use App\Modules\Moderation\Domain\Models\ModerationAction;
use App\Modules\Moderation\Domain\Models\ModerationCase;
use App\Modules\Shared\Application\Services\SecurityAuditLogger;
use App\Modules\Users\Domain\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ContributionModerationService
{
    protected const MANUAL_FLAG_REASON = 'manual_flag';

    public function __construct(
        protected SecurityAuditLogger $securityAuditLogger,
    ) {}

    public function paginateQueue(int $perPage, ?ContributionStatus $status = null): LengthAwarePaginator
    {
        return Contribution::query()
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
            ])
            ->withCount([
                'moderationActions as reports_count' => fn (Builder $query): Builder => $query
                    ->where('action', ModerationActionType::Reported->value),
            ])
            ->whereIn('status', $status ? [$status->value] : ContributionStatus::reviewQueueValues())
            ->orderByRaw('case when status = ? then 0 else 1 end', [ContributionStatus::Flagged->value])
            ->orderBy('created_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function loadDetail(Contribution $contribution): Contribution
    {
        return $contribution->load([
            'subject',
            'submitter.profile',
            'reviewer.profile',
            'moderationCases' => fn ($query) => $query
                ->with(['reporter.profile', 'assignee.profile'])
                ->withCount([
                    'actions as reports_count' => fn (Builder $actionQuery): Builder => $actionQuery
                        ->where('action', ModerationActionType::Reported->value),
                ])
                ->latest('created_at'),
            'moderationActions' => fn ($query) => $query
                ->with(['actor.profile', 'moderationCase'])
                ->latest('created_at'),
        ])->loadCount([
            'moderationActions as reports_count' => fn (Builder $query): Builder => $query
                ->where('action', ModerationActionType::Reported->value),
        ]);
    }

    public function report(
        User $reporter,
        Contribution $contribution,
        ReportContributionData $payload,
        Request $request,
    ): array {
        if (! $contribution->status->canBeReported()) {
            throw ValidationException::withMessages([
                'contribution' => ['Only approved or flagged contributions can be reported.'],
            ]);
        }

        $this->ensureNoDuplicateOpenReport($reporter, $contribution, $payload->reason->value);

        return DB::transaction(function () use ($reporter, $contribution, $payload, $request): array {
            $moderationCase = $this->firstActiveCase($contribution);

            if ($moderationCase === null) {
                $moderationCase = ModerationCase::query()->create([
                    'subject_type' => $contribution->subject_type,
                    'subject_id' => $contribution->subject_id,
                    'contribution_id' => $contribution->id,
                    'reported_by_user_id' => $reporter->id,
                    'assigned_to_user_id' => null,
                    'status' => ModerationCaseStatus::Open,
                    'reason_code' => $payload->reason->value,
                    'notes' => $payload->notes,
                    'resolved_at' => null,
                ]);
            } else {
                $moderationCase->fill([
                    'reported_by_user_id' => $moderationCase->reported_by_user_id ?: $reporter->id,
                    'reason_code' => $moderationCase->reason_code ?: $payload->reason->value,
                ])->save();
            }

            $fromStatus = $contribution->status;
            $toStatus = $fromStatus === ContributionStatus::Approved
                ? ContributionStatus::Flagged
                : $fromStatus;

            if ($toStatus !== $fromStatus) {
                $contribution->forceFill([
                    'status' => $toStatus,
                ])->save();
            }

            ModerationAction::query()->create([
                'contribution_id' => $contribution->id,
                'moderation_case_id' => $moderationCase->id,
                'actor_user_id' => $reporter->id,
                'action' => ModerationActionType::Reported,
                'from_status' => $fromStatus,
                'to_status' => $toStatus,
                'reason_code' => $payload->reason->value,
                'notes' => $payload->notes,
                'request_id' => $this->requestId($request),
            ]);

            return [
                'contribution' => $contribution->fresh(),
                'moderation_case' => $moderationCase->fresh(),
            ];
        });
    }

    public function moderate(
        User $moderator,
        Contribution $contribution,
        ModerateContributionData $payload,
        Request $request,
    ): Contribution {
        $fromStatus = $contribution->status;
        $toStatus = $payload->targetStatus();

        if (! $fromStatus->canTransitionTo($toStatus)) {
            throw ValidationException::withMessages([
                'action' => ['This moderation action is not allowed for the current contribution state.'],
            ]);
        }

        return DB::transaction(function () use ($moderator, $contribution, $payload, $request, $fromStatus, $toStatus): Contribution {
            $moderationCase = $this->firstActiveCase($contribution);

            if ($toStatus === ContributionStatus::Flagged) {
                $moderationCase = $this->openOrEscalateCase(
                    contribution: $contribution,
                    moderator: $moderator,
                    notes: $payload->notes,
                    moderationCase: $moderationCase,
                );
            }

            $contribution->fill([
                'status' => $toStatus,
                'reviewed_by_user_id' => $moderator->id,
                'review_notes' => $payload->notes,
                'reviewed_at' => now(),
            ])->save();

            if ($toStatus !== ContributionStatus::Flagged) {
                $this->resolveActiveCases($contribution, $moderator, $payload->notes);
            }

            ModerationAction::query()->create([
                'contribution_id' => $contribution->id,
                'moderation_case_id' => $moderationCase?->id,
                'actor_user_id' => $moderator->id,
                'action' => $payload->action,
                'from_status' => $fromStatus,
                'to_status' => $toStatus,
                'reason_code' => null,
                'notes' => $payload->notes,
                'request_id' => $this->requestId($request),
            ]);

            $this->securityAuditLogger->log(
                event: 'moderation.contribution.'.$payload->action->value,
                request: $request,
                context: [
                    'target_type' => 'contribution',
                    'target_id' => (string) $contribution->id,
                    'from_status' => $fromStatus->value,
                    'to_status' => $toStatus->value,
                    'moderation_case_id' => $moderationCase?->id,
                    'contribution_type' => $contribution->type?->value,
                    'subject_type' => ContributionSubjectType::fromModel($contribution->subject_type)?->value,
                    'note_present' => filled($payload->notes),
                ],
                actorId: $moderator->id,
            );

            return $contribution->fresh();
        });
    }

    protected function ensureNoDuplicateOpenReport(User $reporter, Contribution $contribution, string $reasonCode): void
    {
        $exists = ModerationAction::query()
            ->where('contribution_id', $contribution->id)
            ->where('actor_user_id', $reporter->id)
            ->where('action', ModerationActionType::Reported->value)
            ->where('reason_code', $reasonCode)
            ->whereHas('moderationCase', fn (Builder $query): Builder => $query->active())
            ->exists();

        if (! $exists) {
            return;
        }

        throw ValidationException::withMessages([
            'reason_code' => ['You have already reported this contribution for this reason while it is under review.'],
        ]);
    }

    protected function firstActiveCase(Contribution $contribution): ?ModerationCase
    {
        return ModerationCase::query()
            ->where('contribution_id', $contribution->id)
            ->active()
            ->latest('created_at')
            ->first();
    }

    protected function openOrEscalateCase(
        Contribution $contribution,
        User $moderator,
        string $notes,
        ?ModerationCase $moderationCase,
    ): ModerationCase {
        if ($moderationCase === null) {
            return ModerationCase::query()->create([
                'subject_type' => $contribution->subject_type,
                'subject_id' => $contribution->subject_id,
                'contribution_id' => $contribution->id,
                'reported_by_user_id' => null,
                'assigned_to_user_id' => $moderator->id,
                'status' => ModerationCaseStatus::UnderReview,
                'reason_code' => self::MANUAL_FLAG_REASON,
                'notes' => $notes,
                'resolved_at' => null,
            ]);
        }

        $moderationCase->fill([
            'status' => ModerationCaseStatus::UnderReview,
            'assigned_to_user_id' => $moderator->id,
            'notes' => $notes,
            'resolved_at' => null,
        ])->save();

        return $moderationCase;
    }

    protected function resolveActiveCases(Contribution $contribution, User $moderator, string $notes): void
    {
        ModerationCase::query()
            ->where('contribution_id', $contribution->id)
            ->active()
            ->get()
            ->each(function (ModerationCase $moderationCase) use ($moderator, $notes): void {
                $moderationCase->fill([
                    'status' => ModerationCaseStatus::Resolved,
                    'assigned_to_user_id' => $moderator->id,
                    'notes' => $notes,
                    'resolved_at' => now(),
                ])->save();
            });
    }

    protected function requestId(Request $request): ?string
    {
        $requestId = $request->attributes->get('request_id') ?: $request->header('X-Request-Id');

        return is_string($requestId) && $requestId !== '' ? $requestId : null;
    }
}
