<?php

namespace App\Http\Requests\Recipes;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRecipeRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'instructions' => ['required', 'string'],
            'servings' => ['required', 'integer', 'min:1'],
            'flavor_profile' => ['required', 'string', 'max:255'],
            'meal_types' => ['required', 'array', 'min:1'],
            'meal_types.*' => ['string', Rule::in(['Breakfast', 'Lunch', 'Dinner'])],
            'photo' => ['nullable', 'image'],
            'prep_time_minutes' => ['nullable', 'integer', 'min:0'],
            'cook_time_minutes' => ['nullable', 'integer', 'min:0'],
            'ingredients' => ['nullable', 'array'],
            'ingredients.*.ingredient_id' => ['required', 'integer', 'exists:ingredients,id'],
            'ingredients.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'ingredients.*.unit' => ['required', 'string', 'max:50'],
            'ingredients.*.note' => ['nullable', 'string', 'max:255'],
        ];
    }
}
