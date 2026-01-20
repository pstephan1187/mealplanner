<?php

namespace App\Http\Requests\ShoppingListItems;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreShoppingListItemRequest extends FormRequest
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
                'required',
                'integer',
                Rule::exists('shopping_lists', 'id')->where('user_id', $this->user()->id),
            ],
            'ingredient_id' => [
                'required',
                'integer',
                Rule::exists('ingredients', 'id')->where('user_id', $this->user()->id),
            ],
            'quantity' => ['required', 'numeric', 'min:0.01'],
            'unit' => ['required', 'string', 'max:50'],
            'is_purchased' => ['sometimes', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
