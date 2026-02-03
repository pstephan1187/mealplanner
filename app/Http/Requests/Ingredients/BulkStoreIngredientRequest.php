<?php

namespace App\Http\Requests\Ingredients;

use App\Models\GroceryStoreSection;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkStoreIngredientRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'ingredients' => ['required', 'array', 'min:1'],
            'ingredients.*.name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('ingredients', 'name')->where('user_id', $this->user()->id),
                function (string $attribute, mixed $value, Closure $fail): void {
                    preg_match('/ingredients\.(\d+)\.name/', $attribute, $matches);
                    $currentIndex = (int) $matches[1];

                    $names = collect($this->input('ingredients', []))
                        ->pluck('name')
                        ->map(fn ($n) => strtolower(trim($n ?? '')));

                    $duplicates = $names->filter(fn ($n, $i) => $i !== $currentIndex && $n === strtolower(trim($value ?? '')));

                    if ($duplicates->isNotEmpty()) {
                        $fail('Duplicate ingredient name within this request.');
                    }
                },
            ],
            'ingredients.*.grocery_store_id' => [
                'nullable',
                Rule::exists('grocery_stores', 'id')->where('user_id', $this->user()->id),
            ],
            'ingredients.*.grocery_store_section_id' => [
                'nullable',
                'exists:grocery_store_sections,id',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if ($value === null) {
                        return;
                    }

                    preg_match('/ingredients\.(\d+)\./', $attribute, $matches);
                    $index = $matches[1];
                    $storeId = $this->input("ingredients.{$index}.grocery_store_id");

                    if (! $storeId) {
                        $fail('A store must be selected to assign a section.');

                        return;
                    }

                    $section = GroceryStoreSection::find($value);
                    if ($section && $section->grocery_store_id !== (int) $storeId) {
                        $fail('The section must belong to the selected store.');
                    }
                },
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        $ingredients = collect($this->input('ingredients', []))->map(function (array $item): array {
            if (($item['grocery_store_id'] ?? '') === '') {
                $item['grocery_store_id'] = null;
            }
            if (($item['grocery_store_section_id'] ?? '') === '') {
                $item['grocery_store_section_id'] = null;
            }

            return $item;
        })->all();

        $this->merge(['ingredients' => $ingredients]);
    }
}
