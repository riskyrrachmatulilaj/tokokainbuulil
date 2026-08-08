<?php

namespace App\Policies;

use App\Models\Receivable;
use App\Models\User;

class ReceivablePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Receivable $receivable): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Receivable $receivable): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Receivable $receivable): bool
    {
        return $user->isAdmin();
    }

    public function deleteAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function restore(User $user, Receivable $receivable): bool
    {
        return $user->isAdmin();
    }

    public function forceDelete(User $user, Receivable $receivable): bool
    {
        return $user->isAdmin();
    }
}
