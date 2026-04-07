<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

class ProductPolicy
{
    private function hasMarketplacePermission(User $user, string $permission): bool
    {
        $guard = config('auth.defaults.guard', 'web');
        $permissionModel = config('permission.models.permission');

        if (!$permissionModel::query()->where('name', $permission)->where('guard_name', $guard)->exists()) {
            return false;
        }

        return $user->hasPermissionTo($permission);
    }

    public function viewAny(User $user): bool
    {
        return $this->hasMarketplacePermission($user, 'view-products')
            || $user->hasRole(['seller', 'vendedor', 'buyer', 'comprador']);
    }

    public function view(User $user, Product $product): bool
    {
        return $this->hasMarketplacePermission($user, 'view-products')
            || $user->hasRole(['seller', 'vendedor', 'buyer', 'comprador']);
    }

    public function create(User $user): bool
    {
        return $this->hasMarketplacePermission($user, 'create-product')
            || $user->hasRole(['seller', 'vendedor']);
    }

    public function update(User $user, Product $product): bool
    {
        return (
            $this->hasMarketplacePermission($user, 'edit-own-product')
            || $user->hasRole(['seller', 'vendedor'])
        ) && (
            $product->seller_id === $user->id
            || $product->sellerProfile?->user_id === $user->id
        );
    }

    public function delete(User $user, Product $product): bool
    {
        return (
            $this->hasMarketplacePermission($user, 'delete-own-product')
            || $user->hasRole(['seller', 'vendedor'])
        ) && (
            $product->seller_id === $user->id
            || $product->sellerProfile?->user_id === $user->id
        );
    }

    public function suspend(User $user, Product $product): bool
    {
        return $this->hasMarketplacePermission($user, 'suspend-product');
    }
}
