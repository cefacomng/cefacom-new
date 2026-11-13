<?php

namespace App\Policies;

use App\Models\Concerns\BelongsToCustomer;
use App\Models\User;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Model;

/**
 * Base policy for customer-owned records. Only the owning customer is allowed. Everyone else,
 * including other customers and staff, receives "not found" so a record's existence is never revealed.
 *
 * Staff access to customer data is never implicit: a subclass must add it explicitly, per permission,
 * in the batch that needs it (for example Batch 9 customer support views).
 */
abstract class OwnedByCustomerPolicy
{
    /** @param  Model&object{customer_id: int}  $record */
    protected function owner(User $user, Model $record): Response
    {
        /** @var Model $record */
        if (in_array(BelongsToCustomer::class, class_uses_recursive($record), true)
            && $user->isCustomer()
            && $record->isOwnedBy($user)) {
            return Response::allow();
        }

        return Response::denyAsNotFound();
    }

    public function view(User $user, Model $record): Response
    {
        return $this->owner($user, $record);
    }

    public function update(User $user, Model $record): Response
    {
        return $this->owner($user, $record);
    }

    public function delete(User $user, Model $record): Response
    {
        return $this->owner($user, $record);
    }
}
