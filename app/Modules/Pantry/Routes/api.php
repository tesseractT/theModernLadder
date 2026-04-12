<?php

use App\Modules\Pantry\Http\Controllers\DeletePantryItemController;
use App\Modules\Pantry\Http\Controllers\ListPantryItemsController;
use App\Modules\Pantry\Http\Controllers\StorePantryItemController;
use App\Modules\Pantry\Http\Controllers\UpdatePantryItemController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'active.user'])
    ->prefix('me/pantry')
    ->name('me.pantry.')
    ->group(function (): void {
        Route::get('/', ListPantryItemsController::class)->name('index');
        Route::post('/', StorePantryItemController::class)->name('store');
        Route::patch('/{pantryItem}', UpdatePantryItemController::class)->name('update');
        Route::delete('/{pantryItem}', DeletePantryItemController::class)->name('destroy');
    });
