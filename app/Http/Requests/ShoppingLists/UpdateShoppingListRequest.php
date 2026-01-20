<?php

namespace App\Http\Requests\ShoppingLists;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateShoppingListRequest extends FormRequest
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
                'sometimes',
                'integer',
                'exists:meal_plans,id',
                Rule::unique('shopping_lists', 'meal_plan_id')->ignore($this->route('shopping_list')),
            ],
            'display_mode' => ['sometimes', 'string', Rule::in(['manual', 'alphabetical'])],
        ];
    }
}
