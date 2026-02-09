<?php

namespace App\Policies;

use App\Models\ExtensionRequest;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Policy para autorizar acciones sobre solicitudes de ampliación de tiempo (RF-15).
 *
 * CONTEXTO DEL NEGOCIO:
 * - Los PROFESORES solicitan ampliación para evidencias que les fueron asignadas
 * - Los ENCARGADOS DE ACREDITACIÓN revisan y aprueban/rechazan las solicitudes
 * - Un encargado puede TAMBIÉN tener rol de profesor y necesita solicitar para sí mismo
 *
 * REGLAS DE AUTORIZACIÓN:
 * 1. Crear solicitud: Solo profesores/encargados (usuarios con evidencias asignadas)
 * 2. Ver solicitud: El creador, encargados de acreditación y admins
 * 3. Editar solicitud: Solo el creador y solo si está PENDIENTE
 * 4. Eliminar solicitud: Solo el creador y solo si está PENDIENTE
 * 5. Listar todas: Encargados y admins (profesores ven solo las suyas vía filtro)
 */
class ExtensionRequestPolicy
{
    /**
     * Determinar si el usuario puede ver el listado completo de solicitudes.
     *
     * CASOS DE USO:
     * - Encargados: Dashboard con TODAS las solicitudes del sistema
     * - Profesores: Ven solo SUS solicitudes (mediante filtro usuario_id en controller)
     *
     * @param User $user Usuario autenticado
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        // Todos los usuarios autenticados pueden acceder al endpoint
        // (El filtro por rol se aplica en el controller)
        return true;
    }

    /**
     * Determinar si el usuario puede ver una solicitud específica.
     *
     * CASOS DE USO:
     * - Profesor consulta el estado de SU solicitud
     * - Encargado revisa solicitud para aprobar/rechazar
     * - Admin audita solicitudes
     *
     * @param User $user Usuario autenticado
     * @param ExtensionRequest $extensionRequest Solicitud a consultar
     * @return bool
     */
    public function view(User $user, ExtensionRequest $extensionRequest): bool
    {
        // Puede ver si:
        // 1. Es el creador de la solicitud
        // 2. Es encargado de acreditación (necesita revisar)
        // 3. Es admin/superusuario
        return $extensionRequest->usuario_id === $user->usuario_id ||
               $user->hasRole(['Encargado de Acreditación', 'Administrador', 'Superusuario']);
    }

    /**
     * Determinar si el usuario puede crear solicitudes de ampliación.
     *
     * CASOS DE USO:
     * - Profesor solicita ampliación para evidencia asignada a él
     * - Encargado (que también es profesor) solicita para sus propias evidencias
     *
     * NOTA: La validación de que la evidencia esté ASIGNADA al usuario
     *       se realiza en ExtensionTimeRequestService::createRequest()
     *
     * @param User $user Usuario autenticado
     * @return bool
     */
    public function create(User $user): bool
    {
        // Todos los usuarios autenticados pueden crear solicitudes
        return true;
    }

    /**
     * Determinar si el usuario puede actualizar una solicitud.
     *
     * CASOS DE USO:
     * - Profesor corrige motivo o fecha antes de que sea revisada
     * - Superusuario actualiza por excepción
     *
     * RESTRICCIONES:
     * - Solo solicitudes en estado PENDIENTE
     * - Solo el creador o Superusuario
     *
     * @param User $user Usuario autenticado
     * @param ExtensionRequest $extensionRequest Solicitud a actualizar
     * @return bool
     */
    public function update(User $user, ExtensionRequest $extensionRequest): bool
    {
        // No se pueden editar solicitudes ya procesadas
        if (strtolower($extensionRequest->estado) !== ExtensionRequest::ESTADO_PENDIENTE) {
            return false;
        }

        // Solo el creador puede editar su solicitud (o superusuario)
        return $extensionRequest->usuario_id === $user->usuario_id ||
               $user->hasRole('Superusuario');
    }

    /**
     * Determinar si el usuario puede eliminar una solicitud.
     *
     * CASOS DE USO:
     * - Profesor cancela solicitud antes de ser procesada
     * - Superusuario elimina solicitud por error/duplicado
     *
     * RESTRICCIONES:
     * - Solo solicitudes en estado PENDIENTE (no eliminar historial de aprobadas/rechazadas)
     * - Solo el creador o Superusuario
     *
     * @param User $user Usuario autenticado
     * @param ExtensionRequest $extensionRequest Solicitud a eliminar
     * @return bool
     */
    public function delete(User $user, ExtensionRequest $extensionRequest): bool
    {
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
     * CASOS DE USO:
     * - Encargado de acreditación aprueba solicitud y extiende fecha límite
     * - Admin aprueba por excepción
     *
     * RESTRICCIONES:
     * - Solo solicitudes en estado PENDIENTE
     * - Solo encargados/admins
     *
     * @param User $user Usuario autenticado
     * @param ExtensionRequest $extensionRequest Solicitud a aprobar
     * @return bool
     */
    public function approve(User $user, ExtensionRequest $extensionRequest): bool
    {
        // No se puede aprobar si ya está resuelta
        if ($extensionRequest->estado !== ExtensionRequest::ESTADO_PENDIENTE) {
            return false;
        }

        // Solo encargados y admins pueden aprobar
        return $user->hasRole(['Encargado de Acreditación', 'Administrador', 'Superusuario']);
    }

    /**
     * Determinar si el usuario puede rechazar una solicitud.
     *
     * CASOS DE USO:
     * - Encargado rechaza solicitud con motivo insuficiente
     * - Admin rechaza por excepción
     *
     * RESTRICCIONES:
     * - Solo solicitudes en estado PENDIENTE
     * - Solo encargados/admins
     *
     * @param User $user Usuario autenticado
     * @param ExtensionRequest $extensionRequest Solicitud a rechazar
     * @return bool
     */
    public function reject(User $user, ExtensionRequest $extensionRequest): bool
    {
        // No se puede rechazar si ya está resuelta
        if ($extensionRequest->estado !== ExtensionRequest::ESTADO_PENDIENTE) {
            return false;
        }

        // Solo encargados y admins pueden rechazar
        return $user->hasRole(['Encargado de Acreditación', 'Administrador', 'Superusuario']);
    }
}
