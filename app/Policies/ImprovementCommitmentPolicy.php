<?php

namespace App\Policies;

use App\Models\ImprovementCommitment;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Policy para autorizar operaciones sobre compromisos de mejora.
 * 
 * REGLAS:
 * - Superusuario, Administrador y Encargado pueden gestionar compromisos
 * - Profesor solo puede ver los relacionados con sus evidencias
 */
class ImprovementCommitmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('compromisos_mejora.view');
    }

    public function view(User $user, ImprovementCommitment $improvementCommitment): bool
    {
        if (!$user->can('compromisos_mejora.view')) {
            return false;
        }

        // Superusuario, Administrador y Encargado ven todos
        if ($user->hasAnyRole(['Superusuario', 'Administrador', 'Encargado de Acreditación'])) {
            return true;
        }

        // Profesor solo ve compromisos de evidencias asignadas a él
        if ($user->hasRole('Profesor')) {
            return $improvementCommitment->evidence
                ->assignments()
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

    public function update(User $user, ImprovementCommitment $improvementCommitment): bool
    {
        return $user->can('compromisos_mejora.edit') && 
               $user->hasAnyRole(['Superusuario', 'Administrador', 'Encargado de Acreditación']);
    }

    public function delete(User $user, ImprovementCommitment $improvementCommitment): bool
    {
        return $user->can('compromisos_mejora.delete') && 
               $user->hasAnyRole(['Superusuario', 'Administrador']);
    }

    public function restore(User $user, ImprovementCommitment $improvementCommitment): bool
    {
        return $user->hasRole('Superusuario');
    }

    public function forceDelete(User $user, ImprovementCommitment $improvementCommitment): bool
    {
        return $user->hasRole('Superusuario');
    }
}
