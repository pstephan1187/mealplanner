<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShoppingListItem extends Model
{
    /** @use HasFactory<\Database\Factories\ShoppingListItemFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'shopping_list_id',
        'ingredient_id',
        'grocery_store_id',
        'grocery_store_section_id',
        'quantity',
        'unit',
        'is_purchased',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'is_purchased' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function shoppingList(): BelongsTo
    {
        return $this->belongsTo(ShoppingList::class);
    }

    public function ingredient(): BelongsTo
    {
        return $this->belongsTo(Ingredient::class);
    }

    public function groceryStore(): BelongsTo
    {
        return $this->belongsTo(GroceryStore::class);
    }

    public function groceryStoreSection(): BelongsTo
    {
        return $this->belongsTo(GroceryStoreSection::class);
    }
}
