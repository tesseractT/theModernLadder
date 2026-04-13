<?php

use App\Modules\Admin\Http\Controllers\ListAdminAiFailuresController;
use App\Modules\Admin\Http\Controllers\ListAdminAuditEventsController;
use App\Modules\Admin\Http\Controllers\ListAdminFlaggedContributionsController;
use App\Modules\Admin\Http\Controllers\ListAdminModerationActionsController;
use App\Modules\Admin\Http\Controllers\ShowAdminSuspiciousActivityController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'active.user', 'can:access-admin', 'throttle:admin.read'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function (): void {
        Route::get('/moderation/flagged-contributions', ListAdminFlaggedContributionsController::class)
            ->name('moderation.flagged-contributions.index');
        Route::get('/moderation/actions', ListAdminModerationActionsController::class)
            ->name('moderation.actions.index');
        Route::get('/ops/suspicious-activity', ShowAdminSuspiciousActivityController::class)
            ->name('ops.suspicious-activity.show');
        Route::get('/ai/failures', ListAdminAiFailuresController::class)
            ->name('ai.failures.index');
        Route::get('/audit-events', ListAdminAuditEventsController::class)
            ->name('audit-events.index');
    });
