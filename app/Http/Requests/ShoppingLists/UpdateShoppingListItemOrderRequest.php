<?php

namespace App\Http\Requests\ShoppingLists;

use Illuminate\Foundation\Http\FormRequest;

class UpdateShoppingListItemOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['required', 'integer', 'exists:shopping_list_items,id'],
            'items.*.sort_order' => ['required', 'integer', 'min:1'],
        ];
    }
}
