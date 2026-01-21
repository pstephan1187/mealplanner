<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GroceryStoreSection extends Model
{
    /** @use HasFactory<\Database\Factories\GroceryStoreSectionFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'grocery_store_id',
        'name',
        'sort_order',
    ];

    public function groceryStore(): BelongsTo
    {
        return $this->belongsTo(GroceryStore::class);
    }

    public function ingredients(): HasMany
    {
        return $this->hasMany(Ingredient::class);
    }
}
