<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Recipe extends Model
{
    /** @use HasFactory<\Database\Factories\RecipeFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'name',
        'instructions',
        'servings',
        'flavor_profile',
        'meal_types',
        'photo_path',
        'prep_time_minutes',
        'cook_time_minutes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'meal_types' => 'array',
            'servings' => 'integer',
            'prep_time_minutes' => 'integer',
            'cook_time_minutes' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function ingredients(): BelongsToMany
    {
        return $this->belongsToMany(Ingredient::class)
            ->withPivot(['quantity', 'unit', 'note'])
            ->withTimestamps();
    }

    public function mealPlanRecipes(): HasMany
    {
        return $this->hasMany(MealPlanRecipe::class);
    }

    /**
     * @param  Builder<Recipe>  $query
     * @return Builder<Recipe>
     */
    public function scopeForUser(Builder $query, User $user): Builder
    {
        return $query->where('user_id', $user->id);
    }
}
