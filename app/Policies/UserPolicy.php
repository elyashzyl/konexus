<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['super-administrator', 'school-administrator']);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, User $model): bool
    {
        return $user->id === $model->id || $user->hasAnyRole(['super-administrator', 'school-administrator']);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasAnyRole(['super-administrator', 'school-administrator']);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, User $model): bool
    {
        return $user->id === $model->id || $user->hasAnyRole(['super-administrator', 'school-administrator']);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, User $model): bool
    {
        return $user->id !== $model->id && $user->hasAnyRole(['super-administrator']);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, User $model): bool
    {
        return $user->hasAnyRole(['super-administrator']);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, User $model): bool
    {
        return $user->id !== $model->id && $user->hasAnyRole(['super-administrator']);
    }

    /**
     * Determine whether the user can manage the model's roles.
     */
    public function manageRoles(User $user): bool
    {
        return $user->hasAnyRole(['super-administrator', 'school-administrator']);
    }

    /**
     * Determine whether the user can impersonate another account.
     */
    public function impersonate(User $user): bool
    {
        return $user->hasAnyRole(['super-administrator', 'school-administrator']);
    }
}
