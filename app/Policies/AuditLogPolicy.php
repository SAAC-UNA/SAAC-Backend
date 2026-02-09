<?php

namespace App\Policies;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class AuditLogPolicy
{
    /**
     * Determina si el usuario puede ver listados de bitácora.
     * Solo usuarios con rol Superusuario pueden consultar bitácora.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole('Superusuario');
    }

    /**
     * Determina si el usuario puede ver un registro específico de bitácora.
     * Solo usuarios con rol Superusuario pueden consultar bitácora.
     */
    public function view(User $user, AuditLog $auditLog): bool
    {
        return $user->hasRole('Superusuario');
    }

    /**
     * La bitácora NO permite creación manual.
     * Solo se crea automáticamente a través de AuditLogService::log()
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * AUDITORÍA INALTERABLE: Ningún usuario puede modificar registros de bitácora.
     * Criterio de aceptación: "El sistema debe impedir la alteración de los datos ya guardados"
     */
    public function update(User $user, AuditLog $auditLog): bool
    {
        return false;
    }

    /**
     * AUDITORÍA INALTERABLE: Ningún usuario puede eliminar registros de bitácora.
     * Criterio de aceptación: "El sistema debe impedir la alteración de los datos ya guardados"
     */
    public function delete(User $user, AuditLog $auditLog): bool
    {
        return false;
    }

    /**
     * No se permite restaurar registros eliminados (porque no se pueden eliminar)
     */
    public function restore(User $user, AuditLog $auditLog): bool
    {
        return false;
    }

    /**
     * No se permite eliminación permanente
     */
    public function forceDelete(User $user, AuditLog $auditLog): bool
    {
        return false;
    }
}
