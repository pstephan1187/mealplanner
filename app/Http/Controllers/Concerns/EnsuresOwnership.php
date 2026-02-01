<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

trait EnsuresOwnership
{
    /**
     * Ensure the authenticated user owns the given model.
     *
     * For nested resources, pass a relationship to load before checking ownership.
     * The ownership check will then be performed on the loaded relationship.
     *
     * @param  string|null  $throughRelationship  Relationship to load and check ownership on (e.g. 'shoppingList', 'mealPlan')
     */
    protected function ensureOwnership(
        Request $request,
        Model $model,
        string $ownerKey = 'user_id',
        ?string $throughRelationship = null,
    ): void {
        if ($throughRelationship !== null) {
            $model->loadMissing($throughRelationship);
            $model = $model->{$throughRelationship};
        }

        if ($model->{$ownerKey} !== $request->user()->id) {
            abort(404);
        }
    }
}
