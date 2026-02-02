<?php

namespace App\Http\Resources;

use App\Support\FractionConverter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IngredientResource extends JsonResource
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
            'name' => $this->name,
            'grocery_store_id' => $this->grocery_store_id,
            'grocery_store_section_id' => $this->grocery_store_section_id,
            'grocery_store' => GroceryStoreResource::make($this->whenLoaded('groceryStore')),
            'grocery_store_section' => GroceryStoreSectionResource::make($this->whenLoaded('groceryStoreSection')),
            'pivot' => $this->whenPivotLoaded('ingredient_recipe', function (): array {
                return [
                    'quantity' => FractionConverter::toFraction((float) $this->pivot->quantity),
                    'unit' => $this->pivot->unit,
                    'note' => $this->pivot->note,
                ];
            }),
        ];
    }
}
