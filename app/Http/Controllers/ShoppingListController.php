<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\EnsuresOwnership;
use App\Http\Requests\ShoppingLists\StoreShoppingListRequest;
use App\Http\Requests\ShoppingLists\UpdateShoppingListItemOrderRequest;
use App\Http\Requests\ShoppingLists\UpdateShoppingListRequest;
use App\Http\Resources\GroceryStoreResource;
use App\Http\Resources\MealPlanResource;
use App\Http\Resources\ShoppingListResource;
use App\Models\GroceryStore;
use App\Models\MealPlan;
use App\Models\ShoppingList;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class ShoppingListController extends Controller
{
    use EnsuresOwnership;

    public function index(Request $request): InertiaResponse
    {
        $shoppingLists = ShoppingList::query()
            ->where('user_id', $request->user()->id)
            ->with('mealPlan')
            ->latest()
            ->paginate();

        return Inertia::render('shopping-lists/Index', [
            'shoppingLists' => ShoppingListResource::collection($shoppingLists),
        ]);
    }

    public function create(Request $request): InertiaResponse
    {
        $mealPlans = MealPlan::query()
            ->where('user_id', $request->user()->id)
            ->orderByDesc('start_date')
            ->get();

        return Inertia::render('shopping-lists/Create', [
            'mealPlans' => MealPlanResource::collection($mealPlans),
        ]);
    }

    public function store(StoreShoppingListRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $mealPlan = $this->resolveMealPlan($request, $data['meal_plan_id']);

        $shoppingList = $request->user()->shoppingLists()->create([
            'meal_plan_id' => $mealPlan->id,
            'display_mode' => $data['display_mode'] ?? 'manual',
        ]);

        $this->populateItemsFromMealPlan($shoppingList, $mealPlan);

        return redirect()->route('shopping-lists.show', $shoppingList);
    }

    public function show(Request $request, ShoppingList $shoppingList): InertiaResponse
    {
        $this->ensureOwnership($request, $shoppingList);

        $groceryStores = GroceryStore::query()
            ->where('user_id', $request->user()->id)
            ->with('sections')
            ->orderBy('name')
            ->get();

        return Inertia::render('shopping-lists/Show', [
            'shoppingList' => ShoppingListResource::make($shoppingList->load([
                'items.ingredient.groceryStore',
                'items.ingredient.groceryStoreSection',
                'items.groceryStore',
                'items.groceryStoreSection',
                'mealPlan',
            ])),
            'groceryStores' => GroceryStoreResource::collection($groceryStores),
        ]);
    }

    public function edit(Request $request, ShoppingList $shoppingList): InertiaResponse
    {
        $this->ensureOwnership($request, $shoppingList);

        $mealPlans = MealPlan::query()
            ->where('user_id', $request->user()->id)
            ->orderByDesc('start_date')
            ->get();

        return Inertia::render('shopping-lists/Edit', [
            'shoppingList' => ShoppingListResource::make($shoppingList),
            'mealPlans' => MealPlanResource::collection($mealPlans),
        ]);
    }

    public function update(UpdateShoppingListRequest $request, ShoppingList $shoppingList): RedirectResponse
    {
        $this->ensureOwnership($request, $shoppingList);

        $data = $request->validated();

        if (array_key_exists('meal_plan_id', $data)) {
            $mealPlan = $this->resolveMealPlan($request, $data['meal_plan_id']);
            $shoppingList->meal_plan_id = $mealPlan->id;
        }

        $shoppingList->fill(Arr::except($data, ['meal_plan_id']));
        $shoppingList->save();

        return redirect()->route('shopping-lists.show', $shoppingList);
    }

    public function destroy(Request $request, ShoppingList $shoppingList): RedirectResponse
    {
        $this->ensureOwnership($request, $shoppingList);

        $shoppingList->delete();

        return redirect()->route('shopping-lists.index');
    }

    public function updateItemOrder(
        UpdateShoppingListItemOrderRequest $request,
        ShoppingList $shoppingList
    ): RedirectResponse {
        $this->ensureOwnership($request, $shoppingList);

        $items = collect($request->validated('items'));
        $itemIds = $items->pluck('id')->all();

        $ownedItemIds = $shoppingList->items()
            ->whereIn('id', $itemIds)
            ->pluck('id')
            ->all();

        if (count($itemIds) !== count($ownedItemIds)) {
            abort(404);
        }

        foreach ($items as $item) {
            $shoppingList->items()
                ->whereKey($item['id'])
                ->update(['sort_order' => $item['sort_order']]);
        }

        return back();
    }

    protected function resolveMealPlan(Request $request, int $mealPlanId): MealPlan
    {
        return MealPlan::query()
            ->whereKey($mealPlanId)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();
    }

    protected function populateItemsFromMealPlan(ShoppingList $shoppingList, MealPlan $mealPlan): void
    {
        $mealPlan->loadMissing('mealPlanRecipes.recipe.ingredients');

        $items = [];

        foreach ($mealPlan->mealPlanRecipes as $mealPlanRecipe) {
            $recipe = $mealPlanRecipe->recipe;

            if (! $recipe || $recipe->servings < 1) {
                continue;
            }

            $scale = $mealPlanRecipe->servings / $recipe->servings;

            foreach ($recipe->ingredients as $ingredient) {
                $unit = $ingredient->pivot?->unit;
                $quantity = $ingredient->pivot?->quantity;

                if ($unit === null || $quantity === null) {
                    continue;
                }

                $key = "{$ingredient->id}|{$unit}";

                if (! isset($items[$key])) {
                    $items[$key] = [
                        'ingredient_id' => $ingredient->id,
                        'unit' => $unit,
                        'quantity' => 0.0,
                        'name' => $ingredient->name,
                    ];
                }

                $items[$key]['quantity'] += ((float) $quantity) * $scale;
            }
        }

        if ($items === []) {
            return;
        }

        $payload = collect($items)
            ->sortBy('name')
            ->values()
            ->map(function (array $item, int $index): array {
                return [
                    'ingredient_id' => $item['ingredient_id'],
                    'quantity' => number_format($item['quantity'], 2, '.', ''),
                    'unit' => $item['unit'],
                    'sort_order' => $index + 1,
                ];
            })
            ->all();

        $shoppingList->items()->createMany($payload);
    }
}
