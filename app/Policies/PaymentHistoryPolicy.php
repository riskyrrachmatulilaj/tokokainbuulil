<?php

namespace App\Policies;

use App\Models\PaymentHistory;
use App\Models\User;

class PaymentHistoryPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, PaymentHistory $paymentHistory): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, PaymentHistory $paymentHistory): bool
    {
        return false;
    }

    public function delete(User $user, PaymentHistory $paymentHistory): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }

    public function restore(User $user, PaymentHistory $paymentHistory): bool
    {
        return false;
    }

    public function forceDelete(User $user, PaymentHistory $paymentHistory): bool
    {
        return false;
    }
}
