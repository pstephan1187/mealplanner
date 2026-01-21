<?php

namespace App\Http\Requests\ShoppingListItems;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateShoppingListItemRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'shopping_list_id' => [
                'sometimes',
                'integer',
                Rule::exists('shopping_lists', 'id')->where('user_id', $this->user()->id),
            ],
            'ingredient_id' => [
                'sometimes',
                'integer',
                Rule::exists('ingredients', 'id')->where('user_id', $this->user()->id),
            ],
            'grocery_store_id' => [
                'sometimes',
                'nullable',
                'integer',
                Rule::exists('grocery_stores', 'id')->where('user_id', $this->user()->id),
            ],
            'grocery_store_section_id' => [
                'sometimes',
                'nullable',
                'integer',
                'exists:grocery_store_sections,id',
            ],
            'quantity' => ['sometimes', 'numeric', 'min:0.01'],
            'unit' => ['sometimes', 'string', 'max:50'],
            'is_purchased' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'nullable', 'integer', 'min:1'],
        ];
    }
}
