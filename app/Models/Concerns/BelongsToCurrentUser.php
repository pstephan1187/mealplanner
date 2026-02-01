<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

/**
 * Adds a `currentUser` local scope to models that have a `user_id` column.
 *
 * Usage: Model::query()->currentUser()->latest()->paginate()
 *
 * @template TModel of \Illuminate\Database\Eloquent\Model
 */
trait BelongsToCurrentUser
{
    /**
     * Scope the query to only include records belonging to the authenticated user.
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    public function scopeCurrentUser(Builder $query): Builder
    {
        return $query->where($query->getModel()->getTable().'.user_id', auth()->id());
    }
}
