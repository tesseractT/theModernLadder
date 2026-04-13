<?php

namespace App\Modules\Admin\Http\Controllers;

use App\Modules\Admin\Application\Services\AdminOperationsService;
use App\Modules\Admin\Http\Resources\AdminSuspiciousActivityResource;
use App\Modules\Shared\Http\Controllers\ApiController;

class ShowAdminSuspiciousActivityController extends ApiController
{
    public function __invoke(
        AdminOperationsService $adminOperationsService,
    ): AdminSuspiciousActivityResource {
        return AdminSuspiciousActivityResource::make(
            $adminOperationsService->suspiciousActivitySummary(),
        );
    }
}
