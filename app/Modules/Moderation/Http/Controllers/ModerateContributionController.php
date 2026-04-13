<?php

namespace App\Modules\Moderation\Http\Controllers;

use App\Modules\Contributions\Domain\Models\Contribution;
use App\Modules\Moderation\Application\Services\ContributionModerationService;
use App\Modules\Moderation\Http\Requests\ModerateContributionRequest;
use App\Modules\Moderation\Http\Resources\ModerationContributionDetailResource;
use App\Modules\Shared\Http\Controllers\ApiController;
use Illuminate\Http\JsonResponse;

class ModerateContributionController extends ApiController
{
    public function __invoke(
        ModerateContributionRequest $request,
        Contribution $contribution,
        ContributionModerationService $contributionModerationService,
    ): JsonResponse {
        $this->authorize('moderate', $contribution);

        $updatedContribution = $contributionModerationService->moderate(
            moderator: $request->user(),
            contribution: $contribution,
            payload: $request->payload(),
            request: $request,
        );

        return $this->respond([
            'message' => 'Moderation action completed successfully.',
            ...ModerationContributionDetailResource::make(
                $contributionModerationService->loadDetail($updatedContribution),
            )->resolve($request),
        ]);
    }
}
