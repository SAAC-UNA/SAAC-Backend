<?php

namespace App\Policies;

use App\Models\User;
use App\Models\ElementApproval;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Policy para autorizar operaciones sobre aprobaciones de Elements (HU-010 modelo flexible).
 *
 * Permisos utilizados:
 * - aprobaciones.view:    Ver aprobaciones de Elements
 * - aprobaciones.approve: Aprobar Elements
 * - aprobaciones.reject:  Rechazar Elements
 */
class ElementApprovalPolicy
{
    use HandlesAuthorization;

    /**
     * Determina si el usuario puede ver la lista de aprobaciones de Elements.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('aprobaciones.view');
    }

    /**
     * Determina si el usuario puede ver una aprobación específica.
     * - Encargado/Administrador/Superusuario pueden ver todas.
     * - Profesor solo puede ver las de Elements donde tiene asignación
     *   (pendiente HU-009 — cuando exista ELEMENTO_ASIGNACION).
     */
    public function view(User $user, ElementApproval $approval): bool
    {
        if (!$user->can('aprobaciones.view')) {
            return false;
        }

        return true;
    }

    /**
     * Determina si el usuario puede aprobar un elemento.
     */
    public function approve(User $user): bool
    {
        return $user->can('aprobaciones.approve');
    }

    /**
     * Determina si el usuario puede rechazar un elemento.
     */
    public function reject(User $user): bool
    {
        return $user->can('aprobaciones.reject');
    }
}
