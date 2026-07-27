<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\AssetWriteOff;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class AssetWriteOffPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return in_array($user->role, [UserRole::ADMIN, UserRole::EDITOR], true);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, AssetWriteOff $assetWriteOff): bool
    {
        return in_array($user->role, [UserRole::ADMIN, UserRole::EDITOR], true);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return in_array($user->role, [UserRole::ADMIN, UserRole::EDITOR], true);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, AssetWriteOff $assetWriteOff): bool
    {
        return $user->role === UserRole::ADMIN
            || ($user->role === UserRole::EDITOR && $assetWriteOff->asset->custodian_id === $user->employee_id);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, AssetWriteOff $assetWriteOff): bool
    {
        return $user->role === UserRole::ADMIN
            || ($user->role === UserRole::EDITOR && $assetWriteOff->asset->custodian_id === $user->employee_id);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, AssetWriteOff $assetWriteOff): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, AssetWriteOff $assetWriteOff): bool
    {
        return false;
    }
}
