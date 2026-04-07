<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    /**
     * Determina si el usuario puede listar pedidos.
     * - Admin: ve todos
     * - Comprador: ve los propios
     * - Vendedor: ve los de sus productos
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view-all-orders')
            || $user->hasPermissionTo('view-own-orders')
            || $user->hasPermissionTo('view-seller-orders');
    }

    /**
     * Determina si el usuario puede ver un pedido.
     * - Admin: cualquier pedido
     * - Comprador: solo sus propios pedidos
     * - Vendedor: solo pedidos que contengan sus productos
     */
    public function view(User $user, Order $order): bool
    {
        if ($user->hasPermissionTo('view-all-orders')) {
            return true;
        }

        if ($user->hasPermissionTo('view-own-orders') && $order->buyer_id === $user->id) {
            return true;
        }

        if ($user->hasPermissionTo('view-seller-orders')) {
            return $order->items()
                ->whereHas('product', fn($q) => $q->where('seller_id', $user->id))
                ->exists();
        }

        return false;
    }

    /**
     * Determina si el usuario puede crear pedidos.
     * Solo compradores.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create-order');
    }

    /**
     * Determina si el vendedor puede actualizar el estado de un pedido.
     * Solo vendedores cuyos productos estén en el pedido.
     */
    public function updateStatus(User $user, Order $order): bool
    {
        if (!$user->hasPermissionTo('view-seller-orders')) {
            return false;
        }

        return $order->items()
            ->whereHas('product', fn($q) => $q->where('seller_id', $user->id))
            ->exists();
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
