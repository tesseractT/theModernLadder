<?php

namespace App\Modules\Admin\Http\Controllers;

use App\Modules\Admin\Application\Services\AdminOperationsService;
use App\Modules\Admin\Http\Requests\ListAdminFlaggedContributionsRequest;
use App\Modules\Admin\Http\Resources\AdminFlaggedContributionResource;
use App\Modules\Shared\Http\Controllers\ApiController;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ListAdminFlaggedContributionsController extends ApiController
{
    public function __invoke(
        ListAdminFlaggedContributionsRequest $request,
        AdminOperationsService $adminOperationsService,
    ): AnonymousResourceCollection {
        return AdminFlaggedContributionResource::collection(
            $adminOperationsService->paginateFlaggedContributions(
                perPage: $request->perPage(),
                reasonCode: $request->reasonCode(),
                subjectType: $request->subjectType(),
            ),
        );
    }
}
