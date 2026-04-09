<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('manage-users');
    }

    public function view(User $user, User $target): bool
    {
        if ($user->hasPermissionTo('manage-users')) {
            return true;
        }

        return $user->id === $target->id;
    }

    public function update(User $user, User $target): bool
    {
        if ($user->hasPermissionTo('manage-users')) {
            return true;
        }

        return $user->id === $target->id;
    }

    public function suspend(User $user, User $target): bool
    {
        if (!$user->hasPermissionTo('manage-users')) {
            return false;
        }

        return $user->id !== $target->id;
    }

    public function delete(User $user, User $target): bool
    {
        if (!$user->hasPermissionTo('manage-users')) {
            return false;
        }

        return $user->id !== $target->id;
    }

    // Profile Methods
    public function viewBuyerProfile(User $authUser, User $targetUser): bool
    {
        return $authUser->id === $targetUser->id || $authUser->hasPermissionTo('manage-users');
    }

    public function updateBuyerProfile(User $authUser, User $targetUser): bool
    {
        return $authUser->id === $targetUser->id || $authUser->hasPermissionTo('manage-users');
    }

    public function viewSellerProfile(User $authUser, User $targetUser): bool
    {
        return $authUser->id === $targetUser->id || $authUser->hasPermissionTo('manage-users');
    }

    public function updateSellerProfile(User $authUser, User $targetUser): bool
    {
        return $authUser->id === $targetUser->id || $authUser->hasPermissionTo('manage-users');
    }
}
