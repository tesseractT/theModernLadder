<?php

use App\Modules\Shared\Http\Controllers\ApiMetaController;
use Illuminate\Support\Facades\Route;

Route::get('/meta', ApiMetaController::class)->name('api.meta');
