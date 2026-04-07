<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;

class PaymentPolicy
{
    /**
     * Determina si el usuario puede listar pagos.
     * - Admin: ve todos
     * - Comprador: ve los de sus pedidos
     * - Vendedor: ve los de pedidos con sus productos
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['admin', 'comprador', 'buyer', 'vendedor', 'seller']);
    }

    /**
     * Determina si el usuario puede ver el detalle de un pago.
     * - Admin: cualquier pago
     * - Comprador: solo pagos de sus pedidos
     * - Vendedor: solo pagos de pedidos con sus productos
     */
    public function view(User $user, Payment $payment): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        // Buyer can see payment of their own order
        if ($user->id === $payment->order->buyer_id) {
            return true;
        }

        // Seller can see payment if their products are in the order
        if ($user->hasRole(['vendedor', 'seller'])) {
            return $payment->order->items()
                ->whereHas('product', fn($q) => $q->where('seller_id', $user->id))
                ->exists();
        }

        return false;
    }

    /**
     * Determina si el usuario puede crear un pago.
     * Solo el comprador dueño del pedido.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create-order');
    }
}
