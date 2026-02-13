<?php

namespace App\Policies;

use App\Models\Location;
use App\Models\User;

class LocationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('manage locations');
    }

    public function view(User $user, Location $location): bool
    {
        return $user->can('manage locations');
    }

    public function create(User $user): bool
    {
        return $user->can('manage locations');
    }

    public function update(User $user, Location $location): bool
    {
        return $user->can('manage locations');
    }

    public function delete(User $user, Location $location): bool
    {
        return $user->can('manage locations');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('manage locations');
    }
}
