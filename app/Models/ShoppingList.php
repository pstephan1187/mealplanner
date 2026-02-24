<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCurrentUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class ShoppingList extends Model
{
    /** @use HasFactory<\Database\Factories\ShoppingListFactory> */
    use BelongsToCurrentUser, HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'meal_plan_id',
        'display_mode',
        'share_token',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function mealPlan(): BelongsTo
    {
        return $this->belongsTo(MealPlan::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ShoppingListItem::class);
    }

    public function generateShareToken(): string
    {
        if (! $this->share_token) {
            $this->update(['share_token' => Str::uuid()->toString()]);
        }

        return $this->share_token;
    }

    public function revokeShareToken(): void
    {
        $this->update(['share_token' => null]);
    }
}
