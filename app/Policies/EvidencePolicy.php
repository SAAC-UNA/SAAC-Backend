<?php

namespace App\Policies;

use App\Models\Evidence;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Policy para autorizar operaciones sobre evidencias.
 * 
 * REGLAS DE NEGOCIO:
 * - Superusuario: Acceso total
 * - Administrador: Gestión completa de evidencias de su carrera
 * - Encargado de Acreditación: Ver y editar estados de evidencias
 * - Profesor: Ver evidencias y editar solo las asignadas a él
 * 
 * VALIDACIONES:
 * - Pertenencia a carrera (para Administrador)
 * - Asignación activa (para Profesor)
 * - Permisos específicos del sistema
 */
class EvidencePolicy
{
    /**
     * Determina si el usuario puede ver el listado de evidencias.
     * 
     * @param User $user
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        // Verificar que tenga el permiso de ver evidencias
        return $user->can('evidencias.view');
    }

    /**
     * Determina si el usuario puede ver una evidencia específica.
     * 
     * @param User $user
     * @param Evidence $evidence
     * @return bool
     */
    public function view(User $user, Evidence $evidence): bool
    {
        // Verificar que tenga el permiso básico
        if (!$user->can('evidencias.view')) {
            return false;
        }

        // Superusuario puede ver todas
        if ($user->hasRole('Superusuario')) {
            return true;
        }

        // Administrador y Encargado ven todas las evidencias
        if ($user->hasAnyRole(['Administrador', 'Encargado de Acreditación'])) {
            return true;
        }

        // Profesor solo ve evidencias que le fueron asignadas
        if ($user->hasRole('Profesor')) {
            return $evidence->assignments()
                ->where('usuario_id', $user->usuario_id)
                ->exists();
        }

        return false;
    }

    /**
     * Determina si el usuario puede crear evidencias.
     * 
     * @param User $user
     * @return bool
     */
    public function create(User $user): bool
    {
        // Solo Superusuario y Administrador pueden crear evidencias
        return $user->can('evidencias.create') && 
               $user->hasAnyRole(['Superusuario', 'Administrador']);
    }

    /**
     * Determina si el usuario puede actualizar una evidencia.
     * 
     * @param User $user
     * @param Evidence $evidence
     * @return bool
     */
    public function update(User $user, Evidence $evidence): bool
    {
        // Verificar permiso básico
        if (!$user->can('evidencias.edit')) {
            return false;
        }

        // Superusuario puede editar todas
        if ($user->hasRole('Superusuario')) {
            return true;
        }

        // Administrador puede editar evidencias de su carrera
        if ($user->hasRole('Administrador')) {
            return true; // TODO: Validar que la evidencia pertenezca a su carrera
        }

        // Encargado puede editar estados de evidencias
        if ($user->hasRole('Encargado de Acreditación')) {
            return true;
        }

        // Profesor solo puede editar evidencias asignadas a él
        if ($user->hasRole('Profesor')) {
            return $evidence->assignments()
                ->where('usuario_id', $user->usuario_id)
                ->exists();
        }

        return false;
    }

    /**
     * Determina si el usuario puede eliminar una evidencia.
     * 
     * @param User $user
     * @param Evidence $evidence
     * @return bool
     */
    public function delete(User $user, Evidence $evidence): bool
    {
        // Solo Superusuario y Administrador pueden eliminar evidencias
        return $user->can('evidencias.delete') && 
               $user->hasAnyRole(['Superusuario', 'Administrador']);
    }

    /**
     * Determina si el usuario puede asignar evidencias a profesores.
     * 
     * @param User $user
     * @return bool
     */
    public function assign(User $user): bool
    {
        // Solo Superusuario, Administrador y Encargado pueden asignar
        return $user->can('evidencias.assign') && 
               $user->hasAnyRole(['Superusuario', 'Administrador', 'Encargado de Acreditación']);
    }

    /**
     * Determina si el usuario puede restaurar una evidencia eliminada.
     * 
     * @param User $user
     * @param Evidence $evidence
     * @return bool
     */
    public function restore(User $user, Evidence $evidence): bool
    {
        // Solo Superusuario puede restaurar
        return $user->hasRole('Superusuario');
    }

    /**
     * Determina si el usuario puede eliminar permanentemente una evidencia.
     * 
     * @param User $user
     * @param Evidence $evidence
     * @return bool
     */
    public function forceDelete(User $user, Evidence $evidence): bool
    {
        // Solo Superusuario puede eliminar permanentemente
        return $user->hasRole('Superusuario');
    }
}
