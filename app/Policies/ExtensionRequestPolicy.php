<?php

namespace App\Policies;

use App\Models\ExtensionRequest;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Policy para autorizar acciones sobre solicitudes de ampliación.
 * 
 * Reglas:
 * - Cualquier usuario puede crear solicitudes para sus propias asignaciones
 * - Solo Encargados de Acreditación pueden aprobar/rechazar solicitudes
 * - Los usuarios pueden ver sus propias solicitudes
 * - Los encargados pueden ver todas las solicitudes
 */
class ExtensionRequestPolicy
{
    /**
     * Determinar si el usuario puede ver todas las solicitudes.
     * 
     * Solo encargados de acreditación y admins pueden ver todas.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole('encargado_acreditacion') || $user->hasRole('admin');
    }

    /**
     * Determinar si el usuario puede ver una solicitud específica.
     * 
     * Pueden ver:
     * - El usuario que creó la solicitud
     * - Encargados de acreditación
     * - Admins
     */
    public function view(User $user, ExtensionRequest $extensionRequest): bool
    {
        return $extensionRequest->usuario_id === $user->usuario_id || 
               $user->hasRole('encargado_acreditacion') || 
               $user->hasRole('admin');
    }

    /**
     * Determinar si el usuario puede crear solicitudes.
     * 
     * Cualquier usuario autenticado puede crear solicitudes para sus asignaciones.
     * La validación de que la asignación le pertenece se hace en el Service.
     */
    public function create(User $user): bool
    {
        return true; // Cualquier usuario autenticado puede intentar crear
    }

    /**
     * Determinar si el usuario puede aprobar una solicitud.
     * 
     * Solo encargados de acreditación y admins pueden aprobar.
     */
    public function approve(User $user, ExtensionRequest $extensionRequest): bool
    {
        // No se puede aprobar si ya está resuelta
        if ($extensionRequest->estado !== ExtensionRequest::ESTADO_PENDIENTE) {
            return false;
        }

        return $user->hasRole('encargado_acreditacion') || $user->hasRole('admin');
    }

    /**
     * Determinar si el usuario puede rechazar una solicitud.
     * 
     * Solo encargados de acreditación y admins pueden rechazar.
     */
    public function reject(User $user, ExtensionRequest $extensionRequest): bool
    {
        // No se puede rechazar si ya está resuelta
        if ($extensionRequest->estado !== ExtensionRequest::ESTADO_PENDIENTE) {
            return false;
        }

        return $user->hasRole('encargado_acreditacion') || $user->hasRole('admin');
    }

    /**
     * Determinar si el usuario puede eliminar una solicitud.
     * 
     * Solo se pueden eliminar solicitudes pendientes y solo el creador o un admin.
     */
    public function delete(User $user, ExtensionRequest $extensionRequest): bool
    {
        // Solo se pueden eliminar solicitudes pendientes
        if ($extensionRequest->estado !== ExtensionRequest::ESTADO_PENDIENTE) {
            return false;
        }

        // El creador puede eliminar su propia solicitud o un admin
        return $extensionRequest->usuario_id === $user->usuario_id || 
               $user->hasRole('admin');
    }
}
