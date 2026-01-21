<?php

namespace App\Http\Requests\GroceryStoreSections;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreGroceryStoreSectionRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('grocery_store_sections', 'name')
                    ->where('grocery_store_id', $this->route('grocery_store')->id),
            ],
        ];
    }
}
