<?php

use App\Modules\Contributions\Http\Controllers\ReportContributionController;
use App\Modules\Contributions\Http\Controllers\StoreContributionController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'active.user'])
    ->prefix('me/contributions')
    ->name('me.contributions.')
    ->group(function (): void {
        Route::middleware('throttle:contributions.store')
            ->post('/', StoreContributionController::class)
            ->name('store');

        Route::middleware('throttle:contributions.report')
            ->post('/{contribution}/reports', ReportContributionController::class)
            ->name('reports.store');
    });
