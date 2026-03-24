<?php

namespace App\Policies;

use App\Models\AccreditationCycle;
use App\Models\User;

/**
 * Policy para autorización de Ciclos de Acreditación
 * 
 * HU-030: Gestión de Ciclos de Acreditación
 * AC-4: No se puede editar un ciclo inactivo o completado
 * AC-5: Acciones bloqueadas si no tiene el permiso correspondiente
 */
class AccreditationCyclePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('ciclos.view');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, AccreditationCycle $accreditationCycle): bool
    {
        return $user->can('ciclos.view');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('ciclos.create');
    }

    /**
     * Determine whether the user can update the model.
     * AC-4: Solo se puede editar si el ciclo está activo
     * AC-5: Requiere permiso ciclos.edit
     */
    public function update(User $user, AccreditationCycle $accreditationCycle): bool
    {
        if (!$accreditationCycle->isEditable()) {
            return false;
        }

        return $user->can('ciclos.edit');
    }

    /**
     * Reactivar un ciclo inactivo o completado.
     * AC-R: Solo el Superusuario puede reactivar; sigue aplicando AC-6.
     */
    public function reactivate(User $user, AccreditationCycle $cycle): bool
    {
        return $user->can('ciclos.reactivar');
    }

    /**
     * Los ciclos NO se pueden eliminar físicamente.
     * Solo se pueden desactivar mediante update.
     */
    public function delete(User $user, AccreditationCycle $accreditationCycle): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, AccreditationCycle $accreditationCycle): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, AccreditationCycle $accreditationCycle): bool
    {
        return false;
    }
}