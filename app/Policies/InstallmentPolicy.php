<?php

namespace App\Policies;

use App\Models\Installment;
use App\Models\User;

class InstallmentPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Installment $installment): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Installment $installment): bool
    {
        return false;
    }

    public function delete(User $user, Installment $installment): bool
    {
        return $user->isAdmin();
    }

    public function deleteAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function restore(User $user, Installment $installment): bool
    {
        return $user->isAdmin();
    }

    public function forceDelete(User $user, Installment $installment): bool
    {
        return $user->isAdmin();
    }
}
