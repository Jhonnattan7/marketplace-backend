<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function view(User $user, User $target): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        return $user->id === $target->id;
    }

    public function update(User $user, User $target): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        return $user->id === $target->id;
    }

    public function suspend(User $user, User $target): bool
    {
        if (!$user->hasRole('admin')) {
            return false;
        }

        return $user->id !== $target->id;
    }

    public function delete(User $user, User $target): bool
    {
        if (!$user->hasRole('admin')) {
            return false;
        }

        return $user->id !== $target->id;
    }
}
