<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MealPlanResource extends JsonResource
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
            'user_id' => $this->user_id,
            'name' => $this->name,
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'recipes' => RecipeResource::collection($this->whenLoaded('recipes')),
            'meal_plan_recipes' => MealPlanRecipeResource::collection($this->whenLoaded('mealPlanRecipes')),
            'shopping_list' => ShoppingListResource::make($this->whenLoaded('shoppingList')),
        ];
    }
}
