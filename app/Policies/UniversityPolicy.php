<?php

namespace App\Policies;

use App\Models\User;
use App\Models\University;
use Illuminate\Auth\Access\Response;

/**
 * Policy para autorizar operaciones sobre universidades.
 * 
 * REGLAS:
 * - Todos pueden ver universidades
 * - Solo Superusuario puede crear, editar y eliminar
 */
class UniversityPolicy
{
    /**
     * Determina si el usuario puede ver el listado de universidades.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('universidades.view');
    }

    /**
     * Determina si el usuario puede ver una universidad específica.
     */
    public function view(User $user, University $university): bool
    {
        return $user->can('universidades.view');
    }

    /**
     * Determina si el usuario puede crear universidades.
     */
    public function create(User $user): bool
    {
        return $user->can('universidades.create') && $user->hasRole('Superusuario');
    }

    /**
     * Determina si el usuario puede actualizar una universidad.
     */
    public function update(User $user, University $university): bool
    {
        return $user->can('universidades.edit') && $user->hasRole('Superusuario');
    }

    /**
     * Determina si el usuario puede eliminar una universidad.
     */
    public function delete(User $user, University $university): bool
    {
        return $user->can('universidades.delete') && $user->hasRole('Superusuario');
    }

    /**
     * Determina si el usuario puede restaurar una universidad.
     */
    public function restore(User $user, University $university): bool
    {
        return $user->hasRole('Superusuario');
    }

    /**
     * Determina si el usuario puede eliminar permanentemente una universidad.
     */
    public function forceDelete(User $user, University $university): bool
    {
        return $user->hasRole('Superusuario');
    }
}
