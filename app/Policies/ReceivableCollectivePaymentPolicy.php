<?php

namespace App\Policies;

use App\Models\ReceivableCollectivePayment;
use App\Models\User;

class ReceivableCollectivePaymentPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, ReceivableCollectivePayment $collectivePayment): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, ReceivableCollectivePayment $collectivePayment): bool
    {
        return false;
    }

    public function delete(User $user, ReceivableCollectivePayment $collectivePayment): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }

    public function restore(User $user, ReceivableCollectivePayment $collectivePayment): bool
    {
        return false;
    }

    public function forceDelete(User $user, ReceivableCollectivePayment $collectivePayment): bool
    {
        return false;
    }
}
