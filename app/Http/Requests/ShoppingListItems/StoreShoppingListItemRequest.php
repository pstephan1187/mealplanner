<?php

namespace App\Http\Requests\ShoppingListItems;

use App\Models\GroceryStoreSection;
use Closure;
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
                'prohibited_if:grocery_store_id,null',
                'prohibited_if:grocery_store_id,',
                'exists:grocery_store_sections,id',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if ($value === null || $this->grocery_store_id === null) {
                        return;
                    }

                    $section = GroceryStoreSection::find($value);
                    if ($section && $section->grocery_store_id !== (int) $this->grocery_store_id) {
                        $fail('The section must belong to the selected store.');
                    }
                },
            ],
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->grocery_store_id === '') {
            $this->merge(['grocery_store_id' => null]);
        }
        if ($this->grocery_store_section_id === '') {
            $this->merge(['grocery_store_section_id' => null]);
        }
    }
}
