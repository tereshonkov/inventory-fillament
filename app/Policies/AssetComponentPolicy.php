<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\AssetComponent;
use App\Models\User;

/** У фазі 1 комплектуючі доступні лише admin; ролі editor/viewer додаються у фазі 4. */
class AssetComponentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role === UserRole::ADMIN;
    }

    public function view(User $user, AssetComponent $component): bool
    {
        return $user->role === UserRole::ADMIN;
    }

    public function create(User $user): bool
    {
        return $user->role === UserRole::ADMIN;
    }

    public function update(User $user, AssetComponent $component): bool
    {
        return $user->role === UserRole::ADMIN;
    }

    public function delete(User $user, AssetComponent $component): bool
    {
        return $user->role === UserRole::ADMIN;
    }

    public function restore(User $user, AssetComponent $component): bool
    {
        return false;
    }

    public function forceDelete(User $user, AssetComponent $component): bool
    {
        return false;
    }
}
