<?php

namespace App\Modules\Contributions\Http\Controllers;

use App\Modules\Contributions\Domain\Models\Contribution;
use App\Modules\Contributions\Http\Resources\ContributionResource;
use App\Modules\Moderation\Application\Services\ContributionModerationService;
use App\Modules\Moderation\Domain\Enums\ModerationActionType;
use App\Modules\Moderation\Http\Requests\ReportContributionRequest;
use App\Modules\Moderation\Http\Resources\ModerationCaseResource;
use App\Modules\Shared\Http\Controllers\ApiController;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;

class ReportContributionController extends ApiController
{
    public function __invoke(
        ReportContributionRequest $request,
        Contribution $contribution,
        ContributionModerationService $contributionModerationService,
    ): JsonResponse {
        $this->authorize('report', $contribution);

        $result = $contributionModerationService->report(
            reporter: $request->user(),
            contribution: $contribution,
            payload: $request->payload(),
            request: $request,
        );

        $reportedContribution = $result['contribution']
            ->load([
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
            ->loadCount([
                'moderationActions as reports_count' => fn (Builder $query): Builder => $query
                    ->where('action', ModerationActionType::Reported->value),
            ]);

        $moderationCase = $result['moderation_case']
            ->load(['reporter.profile', 'assignee.profile'])
            ->loadCount([
                'actions as reports_count' => fn (Builder $query): Builder => $query
                    ->where('action', ModerationActionType::Reported->value),
            ]);

        return $this->respond([
            'message' => 'Contribution report submitted successfully.',
            'contribution' => ContributionResource::make($reportedContribution)->resolve($request),
            'moderation_case' => ModerationCaseResource::make($moderationCase)->resolve($request),
        ], 201);
    }
}
