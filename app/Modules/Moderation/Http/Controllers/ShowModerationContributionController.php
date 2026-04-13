<?php

namespace App\Modules\Moderation\Http\Controllers;

use App\Modules\Contributions\Domain\Models\Contribution;
use App\Modules\Moderation\Application\Services\ContributionModerationService;
use App\Modules\Moderation\Http\Resources\ModerationContributionDetailResource;
use App\Modules\Shared\Http\Controllers\ApiController;

class ShowModerationContributionController extends ApiController
{
    public function __invoke(
        Contribution $contribution,
        ContributionModerationService $contributionModerationService,
    ): ModerationContributionDetailResource {
        $this->authorize('view', $contribution);

        return ModerationContributionDetailResource::make(
            $contributionModerationService->loadDetail($contribution),
        );
    }
}
