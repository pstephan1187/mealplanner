<?php

namespace App\Http\Requests\Ingredients;

use App\Models\GroceryStoreSection;
use Closure;
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
            'grocery_store_id' => [
                'nullable',
                Rule::exists('grocery_stores', 'id')->where('user_id', $this->user()->id),
            ],
            'grocery_store_section_id' => [
                'nullable',
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
