<?php

use App\Modules\Shared\Http\Controllers\ApiHealthController;
use App\Modules\Shared\Http\Controllers\ApiMetaController;
use Illuminate\Support\Facades\Route;

Route::get('/health', ApiHealthController::class)->name('api.health');
Route::get('/meta', ApiMetaController::class)->name('api.meta');
