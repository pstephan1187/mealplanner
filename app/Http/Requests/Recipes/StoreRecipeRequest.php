<?php

namespace App\Http\Requests\Recipes;

use App\Rules\Fraction;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Mews\Purifier\Facades\Purifier;

class StoreRecipeRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->has('instructions')) {
            $this->merge([
                'instructions' => Purifier::clean($this->input('instructions')),
            ]);
        }

        if ($this->has('sections')) {
            $sections = $this->input('sections', []);
            foreach ($sections as $index => $section) {
                if (! empty($section['instructions'])) {
                    $sections[$index]['instructions'] = Purifier::clean($section['instructions']);
                }
            }
            $this->merge(['sections' => $sections]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'instructions' => ['required_without:sections', 'nullable', 'string'],
            'servings' => ['required', 'integer', 'min:1'],
            'flavor_profile' => ['required', 'string', 'max:255'],
            'photo' => ['nullable', 'image', 'prohibits:photo_url'],
            'photo_url' => ['nullable', 'url:http,https', 'prohibits:photo'],
            'prep_time_minutes' => ['nullable', 'integer', 'min:0'],
            'cook_time_minutes' => ['nullable', 'integer', 'min:0'],
            'ingredients' => ['nullable', 'array', 'prohibits:sections'],
            'ingredients.*.ingredient_id' => ['required', 'integer', 'exists:ingredients,id'],
            'ingredients.*.quantity' => ['required', new Fraction],
            'ingredients.*.unit' => ['required', 'string', 'max:50'],
            'ingredients.*.note' => ['nullable', 'string', 'max:255'],
            'sections' => ['nullable', 'array', 'prohibits:ingredients'],
            'sections.*.name' => ['required', 'string', 'max:255'],
            'sections.*.sort_order' => ['required', 'integer', 'min:0'],
            'sections.*.instructions' => ['nullable', 'string'],
            'sections.*.ingredients' => ['nullable', 'array'],
            'sections.*.ingredients.*.ingredient_id' => ['required', 'integer', 'exists:ingredients,id'],
            'sections.*.ingredients.*.quantity' => ['required', new Fraction],
            'sections.*.ingredients.*.unit' => ['required', 'string', 'max:50'],
            'sections.*.ingredients.*.note' => ['nullable', 'string', 'max:255'],
        ];
    }
}
