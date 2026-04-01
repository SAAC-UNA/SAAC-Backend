<?php

namespace App\Policies;

use App\Models\ElementCommitment;
use App\Models\User;

/**
 * Policy para Compromisos de Mejora (modelo flexible).
 * Equivalente a ImprovementCommitmentPolicy pero para ElementCommitment.
 *
 * REGLAS:
 * - Superusuario, Administrador y Encargado pueden gestionar compromisos
 * - Profesor solo puede ver los relacionados con sus asignaciones de Elements
 */
class ElementCommitmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('compromisos_mejora.view');
    }

    public function view(User $user, ElementCommitment $commitment): bool
    {
        if (!$user->can('compromisos_mejora.view')) {
            return false;
        }

        if ($user->hasAnyRole(['Superusuario', 'Administrador', 'Encargado de Acreditación'])) {
            return true;
        }

        // Profesor solo ve compromisos donde tiene asignaciones de Elements vinculadas
        if ($user->hasRole('Profesor')) {
            return $commitment->assignedElements()
                ->where('usuario_id', $user->usuario_id)
                ->exists();
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->can('compromisos_mejora.create') &&
               $user->hasAnyRole(['Superusuario', 'Administrador', 'Encargado de Acreditación']);
    }

    public function update(User $user, ElementCommitment $commitment): bool
    {
        return $user->can('compromisos_mejora.edit') &&
               $user->hasAnyRole(['Superusuario', 'Administrador', 'Encargado de Acreditación']);
    }

    public function delete(User $user, ElementCommitment $commitment): bool
    {
        return $user->can('compromisos_mejora.delete') &&
               $user->hasAnyRole(['Superusuario', 'Administrador']);
    }

    public function restore(User $user, ElementCommitment $commitment): bool
    {
        return $user->hasRole('Superusuario');
    }

    public function forceDelete(User $user, ElementCommitment $commitment): bool
    {
        return $user->hasRole('Superusuario');
    }
}
