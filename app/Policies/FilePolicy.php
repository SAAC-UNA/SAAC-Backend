<?php

namespace App\Policies;

use App\Models\File;
use App\Models\User;
use App\Models\EvidenceAssignment;

/**
 * Policy para autorización de gestión de archivos (HU-008)
 * 
 * Lógica de negocio especial:
 * - Upload: Solo usuarios con asignación activa a la evidencia
 * - Download: Archivos públicos, propietarios, o usuarios asignados
 * - MakePublic: Solo con permiso archivos.make_public
 * - Delete: Propietarios o usuarios con permiso archivos.delete
 * 
 * Permisos utilizados:
 * - archivos.view: Ver metadatos y listar archivos
 * - archivos.upload: Subir archivos
 * - archivos.download: Descargar archivos
 * - archivos.delete: Eliminar archivos
 * - archivos.make_public: Generar enlaces públicos
 */
class FilePolicy
{
    /**
     * Determina si el usuario puede subir archivos a una evidencia específica.
     * 
     * Lógica de negocio: Solo usuarios con asignación activa a la evidencia pueden subir.
     */
    public function upload(User $user, int $evidenciaId): bool
    {
        // Verificar permiso base
        if (!$user->can('archivos.upload')) {
            return false;
        }

        // Verificar que el usuario tenga una asignación a esta evidencia
        return EvidenceAssignment::where('usuario_id', $user->usuario_id)
            ->where('evidencia_id', $evidenciaId)
            ->exists();
    }

    /**
     * Determina si el usuario puede descargar un archivo.
     * 
     * Lógica de negocio:
     * - Archivos públicos válidos: todos pueden descargar
     * - Propietarios: siempre pueden descargar
     * - Usuarios asignados a la evidencia: pueden descargar
     */
    public function download(User $user, File $archivo): bool
    {
        // Si el archivo es público y válido, permitir descarga
        if ($archivo->isPubliclyAccessible()) {
            return true;
        }

        // Verificar permiso base
        if (!$user->can('archivos.download')) {
            return false;
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
     */
    public function makePublic(User $user, File $archivo): bool
    {
        return $user->can('archivos.make_public');
    }

    /**
     * Determina si el usuario puede revocar el acceso público de un archivo.
     */
    public function revokePublicAccess(User $user, File $archivo): bool
    {
        return $user->can('archivos.make_public');
    }

    /**
     * Determina si el usuario puede eliminar un archivo.
     * 
     * Lógica de negocio: Propietarios pueden eliminar sus archivos,
     * o usuarios con permiso explícito de eliminación.
     */
    public function delete(User $user, File $archivo): bool
    {
        // Si es el propietario del archivo, puede eliminarlo
        if ($archivo->usuario_id === $user->usuario_id) {
            return true;
        }

        // Si tiene permiso explícito de eliminación
        return $user->can('archivos.delete');
    }

    /**
     * Determina si el usuario puede ver los metadatos de un archivo.
     */
    public function view(User $user, File $archivo): bool
    {
        return $user->can('archivos.view');
    }

    /**
     * Determina si el usuario puede listar archivos de una evidencia.
     */
    public function viewAny(User $user, int $evidenciaId): bool
    {
        return $user->can('archivos.view');
    }

    /**
     * Determina si el usuario puede generar enlaces públicos masivamente.
     */
    public function bulkMakePublic(User $user): bool
    {
        return $user->can('archivos.make_public');
    }
}
