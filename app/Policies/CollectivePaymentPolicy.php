<?php

namespace App\Policies;

use App\Models\CollectivePayment;
use App\Models\User;

class CollectivePaymentPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, CollectivePayment $collectivePayment): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, CollectivePayment $collectivePayment): bool
    {
        return false;
    }

    public function delete(User $user, CollectivePayment $collectivePayment): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }

    public function restore(User $user, CollectivePayment $collectivePayment): bool
    {
        return false;
    }

    public function forceDelete(User $user, CollectivePayment $collectivePayment): bool
    {
        return false;
    }
}
