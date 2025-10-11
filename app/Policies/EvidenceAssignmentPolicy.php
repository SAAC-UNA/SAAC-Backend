<?php

namespace App\Policies;

use App\Models\EvidenceAssignment;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class EvidenceAssignmentPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Solo los encargados de acreditación pueden ver todas las asignaciones
        return $user->hasRole('encargado_acreditacion') || $user->hasRole('admin');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, EvidenceAssignment $evidenceAssignment): bool
    {
        // Los usuarios pueden ver sus propias asignaciones o los encargados pueden ver todas
        return $evidenceAssignment->usuario_id === $user->usuario_id || 
               $user->hasRole('encargado_acreditacion') || 
               $user->hasRole('admin');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Solo los encargados de acreditación pueden crear asignaciones
        return $user->hasRole('encargado_acreditacion') || $user->hasRole('admin');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, EvidenceAssignment $evidenceAssignment): bool
    {
        // Los usuarios pueden actualizar sus propias asignaciones (cambiar estado) 
        // o los encargados pueden actualizar cualquiera
        return $evidenceAssignment->usuario_id === $user->usuario_id || 
               $user->hasRole('encargado_acreditacion') || 
               $user->hasRole('admin');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, EvidenceAssignment $evidenceAssignment): bool
    {
        // Solo los encargados de acreditación pueden eliminar asignaciones
        return $user->hasRole('encargado_acreditacion') || $user->hasRole('admin');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, EvidenceAssignment $evidenceAssignment): bool
    {
        return $user->hasRole('encargado_acreditacion') || $user->hasRole('admin');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, EvidenceAssignment $evidenceAssignment): bool
    {
        return $user->hasRole('admin');
    }
}