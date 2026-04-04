<?php

namespace App\Policies;

use App\Models\ExtensionRequest;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Policy para autorizar acciones sobre solicitudes de ampliación de tiempo (HU-016).
 *
 * CONTEXTO DEL NEGOCIO:
 * - Los PROFESORES solicitan ampliación para evidencias que les fueron asignadas
 * - Los ENCARGADOS DE ACREDITACIÓN revisan y aprueban/rechazan las solicitudes
 * - Un encargado puede TAMBIÉN tener rol de profesor y necesita solicitar para sí mismo
 *
 * PERMISOS UTILIZADOS:
 * - solicitudes_ampliacion.view: Ver solicitudes de ampliación
 * - solicitudes_ampliacion.create: Crear solicitudes
 * - solicitudes_ampliacion.edit: Editar solicitudes (solo propias y pendientes)
 * - solicitudes_ampliacion.delete: Eliminar solicitudes (solo propias y pendientes)
 * - solicitudes_ampliacion.approve: Aprobar solicitudes
 * - solicitudes_ampliacion.reject: Rechazar solicitudes
 */
class ExtensionRequestPolicy
{
    /**
     * Determinar si el usuario puede ver el listado completo de solicitudes.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('solicitudes_ampliacion.view');
    }

    /**
     * Determinar si el usuario puede ver una solicitud específica.
     * 
     * Lógica de negocio: El creador siempre puede ver su solicitud,
     * y los aprobadores pueden ver todas.
     */
    public function view(User $user, ExtensionRequest $extensionRequest): bool
    {
        // Verificar permiso base
        if (!$user->can('solicitudes_ampliacion.view')) {
            return false;
        }

        // Puede ver si es el creador de la solicitud
        if ($extensionRequest->usuario_id === $user->usuario_id) {
            return true;
        }

        // O si tiene permisos de aprobación (Encargado, Admin, Superusuario)
        return $user->can('solicitudes_ampliacion.approve');
    }

    /**
     * Determinar si el usuario puede crear solicitudes de ampliación.
     */
    public function create(User $user): bool
    {
        return $user->can('solicitudes_ampliacion.create');
    }

    /**
     * Determinar si el usuario puede actualizar una solicitud.
     *
     * RESTRICCIONES:
     * - Solo solicitudes en estado PENDIENTE
     * - Solo el creador (a menos que sea Superusuario)
     */
    public function update(User $user, ExtensionRequest $extensionRequest): bool
    {
        // Verificar permiso base
        if (!$user->can('solicitudes_ampliacion.edit')) {
            return false;
        }

        // No se pueden editar solicitudes ya procesadas
        if (strtolower($extensionRequest->estado) !== ExtensionRequest::ESTADO_PENDIENTE) {
            return false;
        }

        // Solo el creador puede editar su solicitud (o superusuario con todos los permisos)
        return $extensionRequest->usuario_id === $user->usuario_id ||
               $user->hasRole('Superusuario');
    }

    /**
     * Determinar si el usuario puede eliminar una solicitud.
     *
     * RESTRICCIONES:
     * - Solo solicitudes en estado PENDIENTE (no eliminar historial de aprobadas/rechazadas)
     * - Solo el creador (a menos que sea Superusuario)
     */
    public function delete(User $user, ExtensionRequest $extensionRequest): bool
    {
        // Verificar permiso base
        if (!$user->can('solicitudes_ampliacion.delete')) {
            return false;
        }

        // Solo se pueden eliminar solicitudes pendientes (preservar historial)
        if (strtolower($extensionRequest->estado) !== ExtensionRequest::ESTADO_PENDIENTE) {
            return false;
        }

        // Solo el creador puede eliminar su solicitud (o superusuario)
        return $extensionRequest->usuario_id === $user->usuario_id ||
               $user->hasRole('Superusuario');
    }

    /**
     * Determinar si el usuario puede aprobar una solicitud.
     *
     * RESTRICCIONES:
     * - Solo solicitudes en estado PENDIENTE
     */
    public function approve(User $user, ExtensionRequest $extensionRequest): bool
    {
        // Verificar permiso
        if (!$user->can('solicitudes_ampliacion.approve')) {
            return false;
        }

        // No se puede aprobar si ya está resuelta
        return $extensionRequest->estado === ExtensionRequest::ESTADO_PENDIENTE;
    }

    /**
     * Determinar si el usuario puede rechazar una solicitud.
     *
     * RESTRICCIONES:
     * - Solo solicitudes en estado PENDIENTE
     */
    public function reject(User $user, ExtensionRequest $extensionRequest): bool
    {
        // Verificar permiso
        if (!$user->can('solicitudes_ampliacion.reject')) {
            return false;
        }

        // No se puede rechazar si ya está resuelta
        return $extensionRequest->estado === ExtensionRequest::ESTADO_PENDIENTE;
    }

    /**
     * Determinar si el usuario puede cancelar una solicitud.
     *
     * RESTRICCIONES:
     * - Solo solicitudes en estado PENDIENTE
     * - Solo el creador (a menos que sea Superusuario)
     */
    public function cancel(User $user, ExtensionRequest $extensionRequest): bool
    {
        if (!$user->can('solicitudes_ampliacion.cancel')) {
            return false;
        }

        if (strtolower($extensionRequest->estado) !== ExtensionRequest::ESTADO_PENDIENTE) {
            return false;
        }

        return $extensionRequest->usuario_id === $user->usuario_id ||
               $user->hasRole('Superusuario');
    }
}
