<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Department;
use App\Models\User;

class DepartmentPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return in_array($user->role, [UserRole::EDITOR, UserRole::ADMIN], true);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Department $department): bool
    {
        return in_array($user->role, [UserRole::EDITOR, UserRole::ADMIN], true);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return in_array($user->role, [UserRole::EDITOR, UserRole::ADMIN], true);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Department $department): bool
    {
        return in_array($user->role, [UserRole::EDITOR, UserRole::ADMIN], true);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Department $department): bool
    {
        return in_array($user->role, [UserRole::ADMIN], true);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Department $department): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Department $department): bool
    {
        return false;
    }
}
