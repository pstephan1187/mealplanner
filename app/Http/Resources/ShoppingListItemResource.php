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
        return [
            'id' => $this->id,
            'shopping_list_id' => $this->shopping_list_id,
            'ingredient_id' => $this->ingredient_id,
            'quantity' => $this->quantity,
            'unit' => $this->unit,
            'is_purchased' => $this->is_purchased,
            'sort_order' => $this->sort_order,
            'ingredient' => IngredientResource::make($this->whenLoaded('ingredient')),
        ];
    }
}
