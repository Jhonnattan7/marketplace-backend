<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

class ProductPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view-products');
    }

    public function view(User $user, Product $product): bool
    {
        return $user->hasPermissionTo('view-products');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create-product');
    }

    public function update(User $user, Product $product): bool
    {
        return $user->hasPermissionTo('edit-own-product') && $product->sellerProfile?->user_id === $user->id;
    }

    public function delete(User $user, Product $product): bool
    {
        return $user->hasPermissionTo('delete-own-product') && $product->sellerProfile?->user_id === $user->id;
    }

    public function suspend(User $user, Product $product): bool
    {
        return $user->hasPermissionTo('suspend-product');
    }
}
