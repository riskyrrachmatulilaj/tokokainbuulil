<?php

namespace App\Policies;

use App\Models\ReceivableParty;
use App\Models\User;

class ReceivablePartyPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, ReceivableParty $party): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function import(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, ReceivableParty $party): bool
    {
        return true;
    }

    public function delete(User $user, ReceivableParty $party): bool
    {
        return $user->isAdmin();
    }

    public function deleteAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function restore(User $user, ReceivableParty $party): bool
    {
        return $user->isAdmin();
    }

    public function forceDelete(User $user, ReceivableParty $party): bool
    {
        return $user->isAdmin();
    }
}
