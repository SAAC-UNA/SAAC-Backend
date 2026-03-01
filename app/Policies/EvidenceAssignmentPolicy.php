<?php

namespace App\Policies;

use App\Models\EvidenceAssignment;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Policy para autorizar operaciones sobre asignaciones de evidencias.
 * 
 * REGLAS:
 * - Todos pueden ver asignaciones (filtradas por rol en controller)
 * - Solo Superusuario, Administrador y Encargado pueden crear/editar/eliminar
 */
class EvidenceAssignmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('asignaciones.view');
    }

    public function view(User $user, EvidenceAssignment $evidenceAssignment): bool
    {
        return $user->can('asignaciones.view');
    }

    public function create(User $user): bool
    {
        return $user->can('asignaciones.create') && 
               $user->hasAnyRole(['Superusuario', 'Administrador', 'Encargado de Acreditación']);
    }

    public function update(User $user, EvidenceAssignment $evidenceAssignment): bool
    {
        return $user->can('asignaciones.edit') && 
               $user->hasAnyRole(['Superusuario', 'Administrador', 'Encargado de Acreditación']);
    }

    public function delete(User $user, EvidenceAssignment $evidenceAssignment): bool
    {
        return $user->can('asignaciones.delete') && 
               $user->hasAnyRole(['Superusuario', 'Administrador', 'Encargado de Acreditación']);
    }

    public function restore(User $user, EvidenceAssignment $evidenceAssignment): bool
    {
        return $user->hasRole('Superusuario');
    }

    public function forceDelete(User $user, EvidenceAssignment $evidenceAssignment): bool
    {
        return $user->hasRole('Superusuario');
    }
}