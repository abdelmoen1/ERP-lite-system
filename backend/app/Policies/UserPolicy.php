<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;

class UserPolicy
{
    /**
     * Owner can view users from his own store.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole(UserRole::OWNER);
    }

    /**
     * Owner can update the role of a user
     * from the same store.
     */
    public function updateRole(User $user, User $targetUser): bool
    {
        return $user->hasRole(UserRole::OWNER)
            && $user->store_id === $targetUser->store_id
            && $user->id !== $targetUser->id
            && $targetUser->role !== UserRole::OWNER;
    }

    /**
     * Owner can delete managers/employees
     * from the same store.
     */
    public function delete(User $user, User $targetUser): bool
    {
        return $user->hasRole(UserRole::OWNER)
            && $user->store_id === $targetUser->store_id
            && $user->id !== $targetUser->id
            && $targetUser->role !== UserRole::OWNER;
    }
}
