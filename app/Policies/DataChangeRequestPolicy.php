<?php

namespace App\Policies;

use App\Models\DataChangeRequest;
use App\Models\User;

class DataChangeRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, DataChangeRequest $request): bool
    {
        return $user->isAdmin() || $request->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, DataChangeRequest $request): bool
    {
        return false;
    }

    public function delete(User $user, DataChangeRequest $request): bool
    {
        return $user->isAdmin();
    }
}
