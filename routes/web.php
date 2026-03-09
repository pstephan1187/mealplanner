<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GroceryStoreController;
use App\Http\Controllers\GroceryStoreSectionController;
use App\Http\Controllers\IngredientController;
use App\Http\Controllers\MealPlanController;
use App\Http\Controllers\MealPlanRecipeController;
use App\Http\Controllers\RecipeController;
use App\Http\Controllers\RecipeImportController;
use App\Http\Controllers\SharedShoppingListController;
use App\Http\Controllers\ShoppingListController;
use App\Http\Controllers\ShoppingListItemController;
use App\Http\Controllers\UploadController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }

    return redirect()->route('login');
})->name('home');

Route::get('shared/shopping-list/{shareToken}', [SharedShoppingListController::class, 'show'])
    ->name('shared.shopping-list.show');
Route::patch('shared/shopping-list/{shareToken}/items/{shoppingListItem}', [SharedShoppingListController::class, 'toggleItem'])
    ->name('shared.shopping-list.toggle-item');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::patch('shopping-lists/{shopping_list}/items/order', [ShoppingListController::class, 'updateItemOrder'])
        ->name('shopping-lists.items.order');

    Route::post('ingredients/quick', [IngredientController::class, 'storeQuick'])
        ->name('ingredients.store-quick');

    Route::post('ingredients/bulk', [IngredientController::class, 'bulkStore'])
        ->name('ingredients.bulk-store');

    Route::post('grocery-stores/quick', [GroceryStoreController::class, 'storeQuick'])
        ->name('grocery-stores.store-quick');
    Route::post('grocery-stores/{grocery_store}/sections/quick', [GroceryStoreSectionController::class, 'storeQuick'])
        ->name('grocery-stores.sections.store-quick');

    Route::post('uploads/images', UploadController::class)->name('uploads.images');

    Route::post('recipes/import', RecipeImportController::class)->name('recipes.import');
    Route::resource('recipes', RecipeController::class);
    Route::resource('ingredients', IngredientController::class);
    Route::resource('grocery-stores', GroceryStoreController::class);
    Route::resource('grocery-stores.sections', GroceryStoreSectionController::class)->only(['store', 'update', 'destroy']);
    Route::resource('meal-plans', MealPlanController::class);
    Route::resource('meal-plan-recipes', MealPlanRecipeController::class);
    Route::get('shopping-lists/{shopping_list}/print', [ShoppingListController::class, 'print'])
        ->name('shopping-lists.print');
    Route::post('shopping-lists/{shopping_list}/share', [ShoppingListController::class, 'enableSharing'])
        ->name('shopping-lists.share.enable');
    Route::delete('shopping-lists/{shopping_list}/share', [ShoppingListController::class, 'disableSharing'])
        ->name('shopping-lists.share.disable');
    Route::post('shopping-lists/{shopping_list}/share/email', [ShoppingListController::class, 'shareViaEmail'])
        ->name('shopping-lists.share.email');
    Route::resource('shopping-lists', ShoppingListController::class);
    Route::resource('shopping-list-items', ShoppingListItemController::class);
});

require __DIR__.'/settings.php';
