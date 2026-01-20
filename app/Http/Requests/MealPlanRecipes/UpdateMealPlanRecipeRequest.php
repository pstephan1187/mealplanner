<?php

namespace App\Http\Requests\MealPlanRecipes;

use App\Models\MealPlan;
use App\Models\MealPlanRecipe;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Date;
use Illuminate\Validation\Rule;

class UpdateMealPlanRecipeRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'meal_plan_id' => [
                'sometimes',
                'integer',
                Rule::exists('meal_plans', 'id')->where('user_id', $this->user()->id),
            ],
            'recipe_id' => [
                'sometimes',
                'integer',
                Rule::exists('recipes', 'id')->where('user_id', $this->user()->id),
            ],
            'date' => [
                'sometimes',
                'date',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $mealPlanId = $this->integer('meal_plan_id') ?: $this->mealPlanRecipe()?->meal_plan_id;

                    if (! $mealPlanId) {
                        return;
                    }

                    $mealPlan = MealPlan::query()
                        ->whereKey($mealPlanId)
                        ->where('user_id', $this->user()->id)
                        ->first();

                    if (! $mealPlan) {
                        return;
                    }

                    $date = Date::parse($value)->startOfDay();
                    $start = Date::parse($mealPlan->start_date)->startOfDay();
                    $end = Date::parse($mealPlan->end_date)->endOfDay();

                    if ($date->lt($start) || $date->gt($end)) {
                        $fail('The date must be within the meal plan date range.');
                    }
                },
            ],
            'meal_type' => ['sometimes', 'string', Rule::in(['Breakfast', 'Lunch', 'Dinner'])],
            'servings' => ['sometimes', 'integer', 'min:1'],
        ];
    }

    protected function mealPlanRecipe(): ?MealPlanRecipe
    {
        $mealPlanRecipe = $this->route('mealPlanRecipe') ?? $this->route('meal_plan_recipe');

        return $mealPlanRecipe instanceof MealPlanRecipe ? $mealPlanRecipe : null;
    }
}
