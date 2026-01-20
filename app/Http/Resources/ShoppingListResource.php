<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShoppingListResource extends JsonResource
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
            'meal_plan_id' => $this->meal_plan_id,
            'display_mode' => $this->display_mode,
            'meal_plan' => MealPlanResource::make($this->whenLoaded('mealPlan')),
            'items' => ShoppingListItemResource::collection($this->whenLoaded('items')),
        ];
    }
}
