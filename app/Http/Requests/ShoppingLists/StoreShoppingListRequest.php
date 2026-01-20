<?php

namespace App\Http\Requests\ShoppingLists;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreShoppingListRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'meal_plan_id' => [
                'required',
                'integer',
                'exists:meal_plans,id',
                Rule::unique('shopping_lists', 'meal_plan_id'),
            ],
            'display_mode' => ['sometimes', 'string', Rule::in(['manual', 'alphabetical'])],
        ];
    }
}
