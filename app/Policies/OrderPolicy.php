<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view-all-orders')
            || $user->hasPermissionTo('view-own-orders')
            || $user->hasPermissionTo('view-seller-orders');
    }

    public function view(User $user, Order $order): bool
    {
        if ($user->hasPermissionTo('view-all-orders')) {
            return true;
        }

        if ($user->hasPermissionTo('view-own-orders')) {
            return $order->buyer_id === $user->id;
        }

        if ($user->hasPermissionTo('view-seller-orders')) {
            return $order->items()
                ->whereHas('product', fn($q) => $q->where('seller_id', $user->id))
                ->exists();
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create-order');
    }

    public function manageReturn(User $user, Order $order): bool
    {
        return $user->hasPermissionTo('manage-returns');
    }

    public function createReturn(User $user, Order $order): bool
    {
        return $user->hasPermissionTo('create-return')
            && $order->buyer_id === $user->id;
    }

    public function viewReturn(User $user, Order $order): bool
    {
        if ($user->hasPermissionTo('manage-returns')) {
            return true;
        }

        if ($user->hasPermissionTo('view-return-status')) {
            if ($user->hasPermissionTo('create-return')) {
                return $order->buyer_id === $user->id;
            }

            return $order->items()
                ->whereHas('product', fn($q) => $q->where('seller_id', $user->id))
                ->exists();
        }

        return false;
    }
}
