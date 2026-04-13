<?php

namespace App\Modules\Admin\Http\Controllers;

use App\Modules\Admin\Application\Services\AdminOperationsService;
use App\Modules\Admin\Http\Requests\ListAdminAiFailuresRequest;
use App\Modules\Admin\Http\Resources\AdminAiFailureEventResource;
use App\Modules\Shared\Http\Controllers\ApiController;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ListAdminAiFailuresController extends ApiController
{
    public function __invoke(
        ListAdminAiFailuresRequest $request,
        AdminOperationsService $adminOperationsService,
    ): AnonymousResourceCollection {
        return AdminAiFailureEventResource::collection(
            $adminOperationsService->paginateAiFailures(
                perPage: $request->perPage(),
                templateId: $request->templateId(),
            ),
        );
    }
}
