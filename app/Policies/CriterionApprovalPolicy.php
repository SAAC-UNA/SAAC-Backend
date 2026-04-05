<?php

namespace App\Policies;

use App\Models\User;
use App\Models\CriterionApproval;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Policy para autorizar operaciones sobre aprobaciones de criterios (HU-010).
 * 
 * Permisos utilizados:
 * - aprobaciones.view: Ver aprobaciones de criterios
 * - aprobaciones.approve: Aprobar criterios
 * - aprobaciones.reject: Rechazar criterios
 */
class CriterionApprovalPolicy
{
    use HandlesAuthorization;

    /**
     * Determina si el usuario puede ver la lista de aprobaciones.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('aprobaciones.view');
    }

    /**
     * Determina si el usuario puede ver una aprobación específica.
     * 
     * Lógica de negocio adicional:
     * - Profesor solo puede ver aprobaciones de criterios donde tiene evidencias asignadas
     */
    public function view(User $user, CriterionApproval $approval): bool
    {
        // Verificar permiso base
        if (!$user->can('aprobaciones.view')) {
            return false;
        }

        // Profesor solo puede ver aprobaciones de criterios donde tiene evidencias asignadas
        if ($user->hasRole('Profesor')) {
            return $approval->criterion
                ->evidences()
                ->whereHas('assignments', function ($query) use ($user) {
                    $query->where('usuario_id', $user->usuario_id);
                })
                ->exists();
        }

        return true;
    }

    /**
     * Determina si el usuario puede aprobar un criterio.
     */
    public function approve(User $user): bool
    {
        return $user->can('aprobaciones.approve');
    }

    /**
     * Determina si el usuario puede rechazar un criterio.
     */
    public function reject(User $user): bool
    {
        return $user->can('aprobaciones.reject');
    }
}
