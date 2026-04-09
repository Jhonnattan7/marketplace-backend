<?php

namespace App\Policies;

use App\Models\OrderReturn;
use App\Models\User;

class OrderReturnPolicy
{
    // Buyer Methods
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view-own-orders');
    }

    public function view(User $user, OrderReturn $return): bool
    {
        return $user->hasPermissionTo('view-own-orders') && $user->buyerProfile?->id === $return->order->buyer_id;
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create-return');
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
