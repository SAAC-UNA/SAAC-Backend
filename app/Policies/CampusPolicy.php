<?php

namespace App\Policies;

use App\Models\Campus;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Policy para autorizar operaciones sobre sedes (campuses).
 * 
 * REGLAS:
 * - Todos pueden ver sedes
 * - Solo Superusuario y Administrador pueden crear y editar
 * - Solo Superusuario puede eliminar
 */
class CampusPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('campuses.view');
    }

    public function view(User $user, Campus $campus): bool
    {
        return $user->can('campuses.view');
    }

    public function create(User $user): bool
    {
        return $user->can('campuses.create') && 
               $user->hasAnyRole(['Superusuario', 'Administrador']);
    }

    public function update(User $user, Campus $campus): bool
    {
        return $user->can('campuses.edit') && 
               $user->hasAnyRole(['Superusuario', 'Administrador']);
    }

    public function delete(User $user, Campus $campus): bool
    {
        return $user->can('campuses.delete') && $user->hasRole('Superusuario');
    }

    public function restore(User $user, Campus $campus): bool
    {
        return $user->hasRole('Superusuario');
    }

    public function forceDelete(User $user, Campus $campus): bool
    {
        return $user->hasRole('Superusuario');
    }
}
