<?php

namespace App\Policies;

use App\Models\User;
use App\Models\CriterionApproval;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Policy para autorizar operaciones sobre aprobaciones de criterios.
 *
 * Roles permitidos:
 * - Superusuario: Acceso total
 * - Encargado de Acreditación: Puede aprobar/rechazar criterios
 * - Profesor: Solo puede VER aprobaciones de criterios donde tiene evidencias asignadas
 * - Administrador: Solo puede VER todas las aprobaciones
 */
class CriterionApprovalPolicy
{
    use HandlesAuthorization;

    /**
     * Determina si el usuario puede ver la lista de aprobaciones.
     *
     * @param User $user
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        // Todos los roles pueden ver aprobaciones
        return $user->hasAnyRole([
            'Superusuario',
            'Encargado de Acreditación',
            'Administrador',
            'Profesor'
        ]);
    }

    /**
     * Determina si el usuario puede ver una aprobación específica.
     *
     * @param User $user
     * @param CriterionApproval $approval
     * @return bool
     */
    public function view(User $user, CriterionApproval $approval): bool
    {
        // Superusuario, Encargado y Administrador pueden ver todas
        if ($user->hasAnyRole(['Superusuario', 'Encargado de Acreditación', 'Administrador'])) {
            return true;
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

        return false;
    }

    /**
     * Determina si el usuario puede aprobar un criterio.
     *
     * @param User $user
     * @return bool
     */
    public function approve(User $user): bool
    {
        // Solo SuperUsuario y Encargado de Acreditación pueden aprobar
        return $user->hasAnyRole(['Superusuario', 'Encargado de Acreditación']);
    }

    /**
     * Determina si el usuario puede rechazar un criterio.
     *
     * @param User $user
     * @return bool
     */
    public function reject(User $user): bool
    {
        // Solo SuperUsuario y Encargado de Acreditación pueden rechazar
        return $user->hasAnyRole(['Superusuario', 'Encargado de Acreditación']);
    }
}
