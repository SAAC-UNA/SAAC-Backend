<?php

namespace App\Policies;

use App\Models\File;
use App\Models\User;
use App\Models\EvidenceAssignment;

class FilePolicy
{
    /**
     * Determina si el usuario puede subir archivos a una evidencia específica.
     * Solo usuarios con asignación activa a la evidencia pueden subir archivos.
     */
    public function upload(User $user, int $evidenciaId): bool
    {
        // Verifica que el usuario tenga una asignación a esta evidencia
        return EvidenceAssignment::where('usuario_id', $user->usuario_id)
            ->where('evidencia_id', $evidenciaId)
            ->exists();
    }

    /**
     * Determina si el usuario puede descargar un archivo.
     * Puede descargar si:
     * - El archivo es público y no ha expirado (sin autenticación necesaria)
     * - Tiene asignación a la evidencia asociada
     * - Es el usuario que subió el archivo
     */
    public function download(User $user, File $archivo): bool
    {
        // Si el archivo es público y válido, permitir descarga
        if ($archivo->isPubliclyAccessible()) {
            return true;
        }

        // Si es el usuario que subió el archivo
        if ($archivo->usuario_id === $user->usuario_id) {
            return true;
        }

        // Si tiene asignación a la evidencia
        return EvidenceAssignment::where('usuario_id', $user->usuario_id)
            ->where('evidencia_id', $archivo->evidencia_id)
            ->exists();
    }

    /**
     * Determina si el usuario puede hacer público un archivo.
     * Solo usuarios con roles específicos pueden generar enlaces públicos:
     * - Vicerrectoría de Docencia
     * - Administrador (Coordinador de Carrera)
     * - Superusuario
     */
    public function makePublic(User $user, File $archivo): bool
    {
        return $user->hasAnyRole([
            'Superusuario',
            'Vicerrectoría de Docencia',
            'Administrador'
        ]);
    }

    /**
     * Determina si el usuario puede revocar el acceso público de un archivo.
     * Mismos permisos que makePublic.
     */
    public function revokePublicAccess(User $user, File $archivo): bool
    {
        return $this->makePublic($user, $archivo);
    }

    /**
     * Determina si el usuario puede eliminar un archivo.
     * Puede eliminar si:
     * - Es el usuario que subió el archivo
     * - Es Administrador o Superusuario
     */
    public function delete(User $user, File $archivo): bool
    {
        // Si es el propietario del archivo
        if ($archivo->usuario_id === $user->usuario_id) {
            return true;
        }

        // Si es administrador o superusuario
        return $user->hasAnyRole(['Superusuario', 'Administrador']);
    }

    /**
     * Determina si el usuario puede ver los metadatos de un archivo.
     * Cualquier usuario autenticado puede ver metadatos (solo lectura).
     */
    public function view(User $user, File $archivo): bool
    {
        // Permitir ver metadatos a cualquier usuario autenticado
        return true;
    }

    /**
     * Determina si el usuario puede listar archivos de una evidencia.
     * Cualquier usuario autenticado puede listar (solo lectura).
     */
    public function viewAny(User $user, int $evidenciaId): bool
    {
        // Permitir listar archivos a cualquier usuario autenticado
        // Es solo información de consulta, no modificación
        return true;
    }

    /**
     * Determina si el usuario puede generar enlaces públicos masivamente.
     * Solo roles administrativos.
     */
    public function bulkMakePublic(User $user): bool
    {
        return $user->hasAnyRole([
            'Superusuario',
            'Vicerrectoría de Docencia',
            'Administrador'
        ]);
    }
}
