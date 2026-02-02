<?php

namespace App\Http\Requests\Recipes;

use App\Rules\Fraction;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRecipeRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'instructions' => ['sometimes', 'string'],
            'servings' => ['sometimes', 'integer', 'min:1'],
            'flavor_profile' => ['sometimes', 'string', 'max:255'],
            'meal_types' => ['sometimes', 'array', 'min:1'],
            'meal_types.*' => ['string', Rule::in(['Breakfast', 'Lunch', 'Dinner'])],
            'photo' => ['sometimes', 'nullable', 'image'],
            'prep_time_minutes' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'cook_time_minutes' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'ingredients' => ['sometimes', 'array'],
            'ingredients.*.ingredient_id' => ['required', 'integer', 'exists:ingredients,id'],
            'ingredients.*.quantity' => ['required', new Fraction],
            'ingredients.*.unit' => ['required', 'string', 'max:50'],
            'ingredients.*.note' => ['nullable', 'string', 'max:255'],
        ];
    }
}
