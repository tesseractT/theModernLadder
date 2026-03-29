<?php

use App\Modules\Users\Http\Controllers\ShowCurrentUserController;
use App\Modules\Users\Http\Controllers\UpdatePreferencesController;
use App\Modules\Users\Http\Controllers\UpdateProfileController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/me', ShowCurrentUserController::class)->name('me.show');
    Route::patch('/me/profile', UpdateProfileController::class)->name('me.profile.update');
    Route::patch('/me/preferences', UpdatePreferencesController::class)->name('me.preferences.update');
});
