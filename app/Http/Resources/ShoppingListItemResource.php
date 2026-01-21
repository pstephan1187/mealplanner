<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShoppingListItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $effectiveStore = $this->whenLoaded('groceryStore', fn () => $this->groceryStore)
            ?? $this->whenLoaded('ingredient', fn () => $this->ingredient?->groceryStore);

        $effectiveSection = $this->whenLoaded('groceryStoreSection', fn () => $this->groceryStoreSection)
            ?? $this->whenLoaded('ingredient', fn () => $this->ingredient?->groceryStoreSection);

        return [
            'id' => $this->id,
            'shopping_list_id' => $this->shopping_list_id,
            'ingredient_id' => $this->ingredient_id,
            'grocery_store_id' => $this->grocery_store_id,
            'grocery_store_section_id' => $this->grocery_store_section_id,
            'quantity' => $this->quantity,
            'unit' => $this->unit,
            'is_purchased' => $this->is_purchased,
            'sort_order' => $this->sort_order,
            'ingredient' => IngredientResource::make($this->whenLoaded('ingredient')),
            'effective_grocery_store' => GroceryStoreResource::make($effectiveStore),
            'effective_grocery_store_section' => GroceryStoreSectionResource::make($effectiveSection),
        ];
    }
}
