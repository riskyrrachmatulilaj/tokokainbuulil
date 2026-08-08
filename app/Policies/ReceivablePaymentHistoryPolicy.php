<?php

namespace App\Policies;

use App\Models\ReceivablePaymentHistory;
use App\Models\User;

class ReceivablePaymentHistoryPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, ReceivablePaymentHistory $paymentHistory): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, ReceivablePaymentHistory $paymentHistory): bool
    {
        return false;
    }

    public function delete(User $user, ReceivablePaymentHistory $paymentHistory): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }

    public function restore(User $user, ReceivablePaymentHistory $paymentHistory): bool
    {
        return false;
    }

    public function forceDelete(User $user, ReceivablePaymentHistory $paymentHistory): bool
    {
        return false;
    }
}
