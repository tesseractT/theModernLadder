<?php

namespace App\Modules\Admin\Http\Controllers;

use App\Modules\Admin\Application\Services\AdminOperationsService;
use App\Modules\Admin\Http\Requests\ListAdminAuditEventsRequest;
use App\Modules\Admin\Http\Resources\AdminAuditEventResource;
use App\Modules\Shared\Http\Controllers\ApiController;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ListAdminAuditEventsController extends ApiController
{
    public function __invoke(
        ListAdminAuditEventsRequest $request,
        AdminOperationsService $adminOperationsService,
    ): AnonymousResourceCollection {
        return AdminAuditEventResource::collection(
            $adminOperationsService->paginateAuditEvents(
                perPage: $request->perPage(),
                event: $request->eventFilter(),
                actorUserId: $request->actorUserId(),
            ),
        );
    }
}
