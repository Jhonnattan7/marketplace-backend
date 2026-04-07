<?php

namespace App\Policies;

use App\Models\OrderReturn;
use App\Models\User;

class OrderReturnPolicy
{
    // Buyer Methods
    public function viewAny(User $user): bool
    {
        return $user->hasRole('buyer');
    }

    public function view(User $user, OrderReturn $return): bool
    {
        return $user->hasRole('buyer') && $user->id === $return->buyer_id;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('buyer');
    }

    // Admin Methods
    public function viewAnyAdmin(User $user): bool
    {
        return $user->hasPermissionTo('manage-returns');
    }

    public function viewAdmin(User $user, OrderReturn $return): bool
    {
        return $user->hasPermissionTo('manage-returns');
    }

    public function updateAdmin(User $user, OrderReturn $return): bool
    {
        return $user->hasPermissionTo('manage-returns');
    }
}
