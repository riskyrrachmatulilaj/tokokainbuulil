<?php

namespace App\Policies;

use App\Models\ReceivableInstallment;
use App\Models\User;

class ReceivableInstallmentPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, ReceivableInstallment $installment): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, ReceivableInstallment $installment): bool
    {
        return false;
    }

    public function delete(User $user, ReceivableInstallment $installment): bool
    {
        return $user->isAdmin();
    }

    public function deleteAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function restore(User $user, ReceivableInstallment $installment): bool
    {
        return $user->isAdmin();
    }

    public function forceDelete(User $user, ReceivableInstallment $installment): bool
    {
        return $user->isAdmin();
    }
}
