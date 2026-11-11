<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\AssetComponent;
use App\Models\User;

/**
 * Комплектуючі наслідують доступ до своєї робочої станції (як документи руху):
 * admin — усе; editor — лише станції, де він МВО; viewer — тільки читання.
 */
class AssetComponentPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, AssetComponent $component): bool
    {
        return $user->role === UserRole::ADMIN
            || $user->role === UserRole::VIEWER
            || $this->ownsStation($user, $component);
    }

    /** Створення відбувається на картці станції, яку editor відкрити може лише для своїх станцій. */
    public function create(User $user): bool
    {
        return in_array($user->role, [UserRole::ADMIN, UserRole::EDITOR], true);
    }

    public function update(User $user, AssetComponent $component): bool
    {
        return $user->role === UserRole::ADMIN || $this->ownsStation($user, $component);
    }

    public function delete(User $user, AssetComponent $component): bool
    {
        return $user->role === UserRole::ADMIN || $this->ownsStation($user, $component);
    }

    public function restore(User $user, AssetComponent $component): bool
    {
        return false;
    }

    public function forceDelete(User $user, AssetComponent $component): bool
    {
        return false;
    }

    private function ownsStation(User $user, AssetComponent $component): bool
    {
        return $user->role === UserRole::EDITOR
            && $user->employee_id !== null
            && $component->asset->custodian_id === $user->employee_id;
    }
}
