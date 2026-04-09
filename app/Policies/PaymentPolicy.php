<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;

class PaymentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view-all-payments')
            || $user->hasPermissionTo('view-own-payments')
            || $user->hasPermissionTo('view-received-payments');
    }

    public function view(User $user, Payment $payment): bool
    {
        if ($user->hasPermissionTo('view-all-payments')) {
            return true;
        }

        if ($user->hasPermissionTo('view-own-payments') && $user->buyerProfile?->id === $payment->order->buyer_id) {
            return true;
        }

        if ($user->hasPermissionTo('view-received-payments')) {
            return $payment->order->items()
                ->whereHas('product', fn($q) => $q->where('seller_profile_id', $user->sellerProfile?->id))
                ->exists();
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create-order');
    }
}
