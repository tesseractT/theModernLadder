<?php

namespace App\Modules\Admin\Http\Controllers;

use App\Modules\Admin\Application\Services\AdminOperationsService;
use App\Modules\Admin\Http\Requests\ListAdminModerationActionsRequest;
use App\Modules\Admin\Http\Resources\AdminModerationActionResource;
use App\Modules\Shared\Http\Controllers\ApiController;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ListAdminModerationActionsController extends ApiController
{
    public function __invoke(
        ListAdminModerationActionsRequest $request,
        AdminOperationsService $adminOperationsService,
    ): AnonymousResourceCollection {
        return AdminModerationActionResource::collection(
            $adminOperationsService->paginateModerationActions(
                perPage: $request->perPage(),
                action: $request->actionFilter(),
                actorUserId: $request->actorUserId(),
            ),
        );
    }
}
