<?php

namespace App\Http\Requests\Ingredients;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateIngredientRequest extends FormRequest
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
                'string',
                'max:255',
                Rule::unique('ingredients', 'name')
                    ->where('user_id', $this->user()->id)
                    ->ignore($this->route('ingredient')),
            ],
        ];
    }
}
