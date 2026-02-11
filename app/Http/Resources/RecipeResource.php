<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class RecipeResource extends JsonResource
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
            'instructions' => $this->instructions,
            'servings' => $this->servings,
            'flavor_profile' => $this->flavor_profile,
            'photo_path' => $this->photo_path,
            'photo_url' => $this->photo_path
                ? Storage::disk('public')->url($this->photo_path)
                : null,
            'prep_time_minutes' => $this->prep_time_minutes,
            'cook_time_minutes' => $this->cook_time_minutes,
            'ingredients' => IngredientResource::collection($this->whenLoaded('ingredients')),
            'sections' => RecipeSectionResource::collection($this->whenLoaded('sections')),
        ];
    }
}
