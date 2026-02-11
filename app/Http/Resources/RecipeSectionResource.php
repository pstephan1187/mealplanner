<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RecipeSectionResource extends JsonResource
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
            'recipe_id' => $this->recipe_id,
            'name' => $this->name,
            'sort_order' => $this->sort_order,
            'instructions' => $this->instructions,
            'ingredients' => IngredientResource::collection($this->whenLoaded('ingredients')),
        ];
    }
}
