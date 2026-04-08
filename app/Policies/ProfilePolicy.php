<?php
namespace App\Policies;

use App\Models\User;

class ProfilePolicy
{
    public function viewBuyerProfile(User $authUser, User $targetUser): bool
    {
        return $authUser->id === $targetUser->id;
    }

    public function updateBuyerProfile(User $authUser, User $targetUser): bool
    {
        return $authUser->id === $targetUser->id;
    }

    public function viewSellerProfile(User $authUser, User $targetUser): bool
    {
        return $authUser->id === $targetUser->id;
    }

    public function updateSellerProfile(User $authUser, User $targetUser): bool
    {
        return $authUser->id === $targetUser->id;
    }
}