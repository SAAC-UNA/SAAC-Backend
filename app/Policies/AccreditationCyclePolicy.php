<?php

namespace App\Policies;

use App\Models\AccreditationCycle;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Policy para autorización de Ciclos de Acreditación
 * 
 * IMPORTANTE: Los ciclos NO se pueden eliminar, solo desactivar.
 * Esto evita pérdida de información histórica de procesos de acreditación.
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
     */
    public function update(User $user, AccreditationCycle $accreditationCycle): bool
    {
        return $user->can('ciclos.edit');
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
