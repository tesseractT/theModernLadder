<?php

use App\Modules\Ingredients\Http\Controllers\ListIngredientsController;
use App\Modules\Ingredients\Http\Controllers\ShowIngredientController;
use Illuminate\Support\Facades\Route;

Route::prefix('ingredients')->name('ingredients.')->group(function (): void {
    Route::get('/', ListIngredientsController::class)->name('index');
    Route::get('/{slug}', ShowIngredientController::class)->name('show');
});
