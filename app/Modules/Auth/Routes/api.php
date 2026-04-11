<?php

use App\Modules\Auth\Http\Controllers\LoginController;
use App\Modules\Auth\Http\Controllers\LogoutController;
use App\Modules\Auth\Http\Controllers\RegisterController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->name('auth.')->group(function (): void {
    Route::middleware('throttle:auth.register')
        ->post('/register', RegisterController::class)
        ->name('register');

    Route::middleware('throttle:auth.login')
        ->post('/login', LoginController::class)
        ->name('login');

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::middleware('throttle:auth.logout')
            ->post('/logout', LogoutController::class)
            ->name('logout');
    });
});
