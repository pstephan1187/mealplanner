<?php

namespace App\Http\Requests\Recipes;

use App\Rules\Fraction;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Mews\Purifier\Facades\Purifier;

class UpdateRecipeRequest extends FormRequest
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
            'name' => ['sometimes', 'string', 'max:255'],
            'instructions' => ['sometimes', 'nullable', 'string'],
            'servings' => ['sometimes', 'integer', 'min:1'],
            'flavor_profile' => ['sometimes', 'string', 'max:255'],
            'photo' => ['sometimes', 'nullable', 'image', 'prohibits:photo_url'],
            'photo_url' => ['sometimes', 'nullable', 'url:http,https', 'prohibits:photo'],
            'prep_time_minutes' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'cook_time_minutes' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'ingredients' => ['sometimes', 'array', 'prohibits:sections'],
            'ingredients.*.ingredient_id' => ['required', 'integer', Rule::exists('ingredients', 'id')->where('user_id', $this->user()->id)],
            'ingredients.*.quantity' => ['required', new Fraction],
            'ingredients.*.unit' => ['required', 'string', 'max:50'],
            'ingredients.*.note' => ['nullable', 'string', 'max:255'],
            'sections' => ['sometimes', 'array', 'prohibits:ingredients'],
            'sections.*.name' => ['required', 'string', 'max:255'],
            'sections.*.sort_order' => ['required', 'integer', 'min:0'],
            'sections.*.instructions' => ['nullable', 'string'],
            'sections.*.ingredients' => ['nullable', 'array'],
            'sections.*.ingredients.*.ingredient_id' => ['required', 'integer', Rule::exists('ingredients', 'id')->where('user_id', $this->user()->id)],
            'sections.*.ingredients.*.quantity' => ['required', new Fraction],
            'sections.*.ingredients.*.unit' => ['required', 'string', 'max:50'],
            'sections.*.ingredients.*.note' => ['nullable', 'string', 'max:255'],
        ];
    }
}
