<?php

use App\Modules\Moderation\Http\Controllers\ListModerationContributionsController;
use App\Modules\Moderation\Http\Controllers\ModerateContributionController;
use App\Modules\Moderation\Http\Controllers\ShowModerationContributionController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'active.user'])
    ->prefix('moderation/contributions')
    ->name('moderation.contributions.')
    ->group(function (): void {
        Route::get('/', ListModerationContributionsController::class)->name('index');
        Route::get('/{contribution}', ShowModerationContributionController::class)->name('show');

        Route::middleware('throttle:moderation.actions')
            ->post('/{contribution}/actions', ModerateContributionController::class)
            ->name('actions.store');
    });
