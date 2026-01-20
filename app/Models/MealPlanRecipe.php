<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MealPlanRecipe extends Model
{
    /** @use HasFactory<\Database\Factories\MealPlanRecipeFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'meal_plan_id',
        'recipe_id',
        'date',
        'meal_type',
        'servings',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'servings' => 'integer',
        ];
    }

    public function mealPlan(): BelongsTo
    {
        return $this->belongsTo(MealPlan::class);
    }

    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
    }
}
