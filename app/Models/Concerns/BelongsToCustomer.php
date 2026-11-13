<?php

namespace App\Models\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Marks a record as owned by exactly one customer (column `customer_id` -> users.id).
 *
 * Rules for every customer-owned model (contacts, campaigns, wallet accounts, ...):
 *  1. The owner is always taken from the signed-in session on the server, never from request input.
 *  2. Read through the owner: `ownedBy($user)` or a relationship on User, never `Model::find($id)`.
 *  3. Authorise with a policy extending OwnedByCustomerPolicy so other customers get "not found".
 *
 * @mixin \Illuminate\Database\Eloquent\Model
 */
trait BelongsToCustomer
{
    /** @return BelongsTo<User, $this> */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeOwnedBy(Builder $query, User $user): Builder
    {
        return $query->where($this->qualifyColumn('customer_id'), $user->getKey());
    }

    public function isOwnedBy(User $user): bool
    {
        return (int) $this->getAttribute('customer_id') === (int) $user->getKey();
    }
}
