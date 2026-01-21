<?php

namespace App\Http\Requests\GroceryStores;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateGroceryStoreRequest extends FormRequest
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
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('grocery_stores', 'name')
                    ->where('user_id', $this->user()->id)
                    ->ignore($this->route('grocery_store')),
            ],
        ];
    }
}
