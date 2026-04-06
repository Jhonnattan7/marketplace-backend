<?php

namespace App\Policies;

use App\Models\Cart;
use App\Models\User;

class CartPolicy
{
    public function view(User $user, Cart $cart): bool
    {
        return $user->hasRole('comprador') && $user->id === $cart->buyer_id;
    }

    public function manage(User $user, Cart $cart): bool
    {
        return $user->hasRole('comprador') && $user->id === $cart->buyer_id;
    }
}
