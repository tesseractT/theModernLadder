<?php

namespace App\Modules\Moderation\Http\Controllers;

use App\Modules\Contributions\Domain\Models\Contribution;
use App\Modules\Contributions\Http\Resources\ContributionResource;
use App\Modules\Moderation\Application\Services\ContributionModerationService;
use App\Modules\Moderation\Http\Requests\ListModerationContributionsRequest;
use App\Modules\Shared\Http\Controllers\ApiController;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ListModerationContributionsController extends ApiController
{
    public function __invoke(
        ListModerationContributionsRequest $request,
        ContributionModerationService $contributionModerationService,
    ): AnonymousResourceCollection {
        $this->authorize('viewAny', Contribution::class);

        return ContributionResource::collection(
            $contributionModerationService->paginateQueue(
                perPage: $request->perPage(),
                status: $request->statusFilter(),
            ),
        );
    }
}
