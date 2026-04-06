<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;

class PaymentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['admin', 'comprador', 'vendedor']);
    }

    public function view(User $user, Payment $payment): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        return $user->id === $payment->order->buyer_id;
    }
}
