<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MealPlanRecipeResource extends JsonResource
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
            'meal_plan_id' => $this->meal_plan_id,
            'recipe_id' => $this->recipe_id,
            'date' => $this->date?->toDateString() ?? $this->date,
            'meal_type' => $this->meal_type,
            'servings' => $this->servings,
            'recipe' => RecipeResource::make($this->whenLoaded('recipe')),
        ];
    }
}
