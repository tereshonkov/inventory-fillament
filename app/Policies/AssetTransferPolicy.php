<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\AssetTransfer;
use App\Models\User;

class AssetTransferPolicy
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
    public function view(User $user, AssetTransfer $assetTransfer): bool
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
    public function update(User $user, AssetTransfer $assetTransfer): bool
    {
        return $user->role === UserRole::ADMIN
            || ($user->role === UserRole::EDITOR && $assetTransfer->asset->custodian_id === $user->employee_id);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, AssetTransfer $assetTransfer): bool
    {
        return $user->role === UserRole::ADMIN
            || ($user->role === UserRole::EDITOR && $assetTransfer->asset->custodian_id === $user->employee_id);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, AssetTransfer $assetTransfer): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, AssetTransfer $assetTransfer): bool
    {
        return false;
    }
}
