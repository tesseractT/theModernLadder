<?php

namespace App\Modules\Contributions\Http\Controllers;

use App\Modules\Contributions\Application\Services\ContributionService;
use App\Modules\Contributions\Domain\Models\Contribution;
use App\Modules\Contributions\Http\Requests\StoreContributionRequest;
use App\Modules\Contributions\Http\Resources\ContributionResource;
use App\Modules\Shared\Http\Controllers\ApiController;
use Illuminate\Http\JsonResponse;

class StoreContributionController extends ApiController
{
    public function __invoke(
        StoreContributionRequest $request,
        ContributionService $contributionService,
    ): JsonResponse {
        $this->authorize('create', Contribution::class);

        $contribution = $contributionService->submit(
            $request->user(),
            $request->payload(),
        )->load([
            'subject',
            'submitter.profile',
            'reviewer.profile',
        ]);

        return $this->respond([
            'message' => 'Contribution submitted successfully.',
            'contribution' => ContributionResource::make($contribution)->resolve($request),
        ], 201);
    }
}
