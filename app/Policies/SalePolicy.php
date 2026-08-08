<?php

namespace App\Policies;

use App\Models\Sale;
use App\Models\User;

class SalePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Sale $sale): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Sale $sale): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Sale $sale): bool
    {
        return $user->isAdmin();
    }

    public function deleteAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function restore(User $user, Sale $sale): bool
    {
        return $user->isAdmin();
    }

    public function forceDelete(User $user, Sale $sale): bool
    {
        return $user->isAdmin();
    }
}
