<?php

namespace App\Policies;

use App\Models\Career;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Policy para autorizar operaciones sobre carreras.
 * 
 * REGLAS:
 * - Todos pueden ver carreras
 * - Superusuario y Administrador pueden crear
 * - Administrador solo puede editar SU carrera
 * - Solo Superusuario puede eliminar
 */
class CareerPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('carreras.view');
    }

    public function view(User $user, Career $career): bool
    {
        return $user->can('carreras.view');
    }

    public function create(User $user): bool
    {
        return $user->can('carreras.create') && 
               $user->hasAnyRole(['Superusuario', 'Administrador']);
    }

    public function update(User $user, Career $career): bool
    {
        if (!$user->can('carreras.edit')) {
            return false;
        }

        // Superusuario puede editar cualquier carrera
        if ($user->hasRole('Superusuario')) {
            return true;
        }

        // Administrador solo puede editar carreras asignadas a él
        if ($user->hasRole('Administrador')) {
            return $user->careers()->where('carrera_id', $career->carrera_id)->exists();
        }

        return false;
    }

    public function delete(User $user, Career $career): bool
    {
        return $user->can('carreras.delete') && $user->hasRole('Superusuario');
    }

    public function restore(User $user, Career $career): bool
    {
        return $user->hasRole('Superusuario');
    }

    public function forceDelete(User $user, Career $career): bool
    {
        return $user->hasRole('Superusuario');
    }
}
