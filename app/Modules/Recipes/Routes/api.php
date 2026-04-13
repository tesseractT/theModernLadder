<?php

use App\Modules\Recipes\Http\Controllers\DeleteRecipePlanItemController;
use App\Modules\Recipes\Http\Controllers\FavoriteRecipeTemplateController;
use App\Modules\Recipes\Http\Controllers\GenerateRecipeTemplateExplanationController;
use App\Modules\Recipes\Http\Controllers\GenerateSuggestionsController;
use App\Modules\Recipes\Http\Controllers\ListFavoriteRecipeTemplatesController;
use App\Modules\Recipes\Http\Controllers\ListRecentRecipeTemplateHistoryController;
use App\Modules\Recipes\Http\Controllers\ListRecipePlanItemsController;
use App\Modules\Recipes\Http\Controllers\ListRecipeTemplatesController;
use App\Modules\Recipes\Http\Controllers\ListSavedRecipeTemplatesController;
use App\Modules\Recipes\Http\Controllers\RemoveSavedRecipeTemplateController;
use App\Modules\Recipes\Http\Controllers\SaveSuggestedRecipeTemplateController;
use App\Modules\Recipes\Http\Controllers\ShowRecipeTemplateController;
use App\Modules\Recipes\Http\Controllers\ShowRecipeTemplateDetailController;
use App\Modules\Recipes\Http\Controllers\StoreRecipePlanItemController;
use App\Modules\Recipes\Http\Controllers\UnfavoriteRecipeTemplateController;
use App\Modules\Recipes\Http\Controllers\UpdateRecipePlanItemController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'active.user'])
    ->prefix('me/suggestions')
    ->name('me.suggestions.')
    ->group(function (): void {
        Route::post('/', GenerateSuggestionsController::class)->name('store');
    });

Route::middleware(['auth:sanctum', 'active.user'])
    ->prefix('recipes/templates')
    ->name('recipes.templates.')
    ->group(function (): void {
        Route::get('/{recipeTemplate}', ShowRecipeTemplateDetailController::class)->name('show');
        Route::middleware('throttle:recipes.explanation')
            ->post('/{recipeTemplate}/explanation', GenerateRecipeTemplateExplanationController::class)
            ->name('explanation.store');
    });

Route::middleware(['auth:sanctum', 'active.user'])
    ->prefix('me/recipe-templates')
    ->name('me.recipe-templates.')
    ->group(function (): void {
        Route::get('/favorites', ListFavoriteRecipeTemplatesController::class)->name('favorites.index');
        Route::middleware('throttle:recipes.interactions.write')
            ->put('/{recipeTemplate}/favorite', FavoriteRecipeTemplateController::class)
            ->name('favorites.store');
        Route::middleware('throttle:recipes.interactions.write')
            ->delete('/{recipeTemplate}/favorite', UnfavoriteRecipeTemplateController::class)
            ->name('favorites.destroy');

        Route::get('/saved-suggestions', ListSavedRecipeTemplatesController::class)->name('saved-suggestions.index');
        Route::middleware('throttle:recipes.interactions.write')
            ->put('/{recipeTemplate}/saved-suggestion', SaveSuggestedRecipeTemplateController::class)
            ->name('saved-suggestions.store');
        Route::middleware('throttle:recipes.interactions.write')
            ->delete('/{recipeTemplate}/saved-suggestion', RemoveSavedRecipeTemplateController::class)
            ->name('saved-suggestions.destroy');

        Route::get('/history', ListRecentRecipeTemplateHistoryController::class)->name('history.index');
    });

Route::middleware(['auth:sanctum', 'active.user'])
    ->prefix('me/recipe-plans')
    ->name('me.recipe-plans.')
    ->group(function (): void {
        Route::get('/', ListRecipePlanItemsController::class)->name('index');
        Route::middleware('throttle:recipes.plan.write')
            ->post('/', StoreRecipePlanItemController::class)
            ->name('store');
        Route::middleware('throttle:recipes.plan.write')
            ->patch('/{recipePlanItem}', UpdateRecipePlanItemController::class)
            ->name('update');
        Route::middleware('throttle:recipes.plan.write')
            ->delete('/{recipePlanItem}', DeleteRecipePlanItemController::class)
            ->name('destroy');
    });

Route::prefix('recipe-templates')->name('recipe-templates.')->group(function (): void {
    Route::get('/', ListRecipeTemplatesController::class)->name('index');
    Route::get('/{slug}', ShowRecipeTemplateController::class)->name('show');
});
