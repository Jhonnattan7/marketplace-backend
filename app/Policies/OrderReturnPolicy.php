<?php

namespace App\Policies;

use App\Models\OrderReturn;
use App\Models\User;

class OrderReturnPolicy
{
    public function viewAny(User $user): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        return $user->hasRole('comprador');
    }

    public function view(User $user, OrderReturn $return): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        return $user->hasRole('comprador') && $user->id === $return->buyer_id;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('comprador');
    }

    public function resolve(User $user): bool
    {
        return $user->hasRole('admin');
    }
}
