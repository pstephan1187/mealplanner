<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GroceryStoreController;
use App\Http\Controllers\GroceryStoreSectionController;
use App\Http\Controllers\IngredientController;
use App\Http\Controllers\MealPlanController;
use App\Http\Controllers\MealPlanRecipeController;
use App\Http\Controllers\RecipeController;
use App\Http\Controllers\RecipeImportController;
use App\Http\Controllers\ShoppingListController;
use App\Http\Controllers\ShoppingListItemController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::patch('shopping-lists/{shopping_list}/items/order', [ShoppingListController::class, 'updateItemOrder'])
        ->name('shopping-lists.items.order');

    Route::post('ingredients/quick', [IngredientController::class, 'storeQuick'])
        ->name('ingredients.store-quick');

    Route::post('grocery-stores/quick', [GroceryStoreController::class, 'storeQuick'])
        ->name('grocery-stores.store-quick');
    Route::post('grocery-stores/{grocery_store}/sections/quick', [GroceryStoreSectionController::class, 'storeQuick'])
        ->name('grocery-stores.sections.store-quick');

    Route::post('recipes/import', RecipeImportController::class)->name('recipes.import');
    Route::resource('recipes', RecipeController::class);
    Route::resource('ingredients', IngredientController::class);
    Route::resource('grocery-stores', GroceryStoreController::class);
    Route::resource('grocery-stores.sections', GroceryStoreSectionController::class)->only(['store', 'update', 'destroy']);
    Route::resource('meal-plans', MealPlanController::class);
    Route::resource('meal-plan-recipes', MealPlanRecipeController::class);
    Route::resource('shopping-lists', ShoppingListController::class);
    Route::resource('shopping-list-items', ShoppingListItemController::class);
});

require __DIR__.'/settings.php';
