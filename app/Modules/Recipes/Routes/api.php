<?php

use App\Modules\Recipes\Http\Controllers\GenerateSuggestionsController;
use App\Modules\Recipes\Http\Controllers\ListRecipeTemplatesController;
use App\Modules\Recipes\Http\Controllers\ShowRecipeTemplateController;
use App\Modules\Recipes\Http\Controllers\ShowRecipeTemplateDetailController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')
    ->prefix('me/suggestions')
    ->name('me.suggestions.')
    ->group(function (): void {
        Route::post('/', GenerateSuggestionsController::class)->name('store');
    });

Route::middleware('auth:sanctum')
    ->prefix('recipes/templates')
    ->name('recipes.templates.')
    ->group(function (): void {
        Route::get('/{recipeTemplate}', ShowRecipeTemplateDetailController::class)->name('show');
    });

Route::prefix('recipe-templates')->name('recipe-templates.')->group(function (): void {
    Route::get('/', ListRecipeTemplatesController::class)->name('index');
    Route::get('/{slug}', ShowRecipeTemplateController::class)->name('show');
});
