<?php

namespace App\Policies;

use App\Models\Component;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Policy para autorización de Componentes del Marco SINAES
 */
class ComponentPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('componentes.view');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Component $component): bool
    {
        return $user->can('componentes.view');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('componentes.create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Component $component): bool
    {
        return $user->can('componentes.edit');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Component $component): bool
    {
        return $user->can('componentes.delete');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Component $component): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Component $component): bool
    {
        return false;
    }
}
