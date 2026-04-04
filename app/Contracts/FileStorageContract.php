<?php

namespace App\Contracts;

use App\Models\File;
use Illuminate\Http\UploadedFile;

/**
 * Contrato para estrategias de almacenamiento de archivos.
 *
 * Cada modelo de acreditación (tradicional, flexible) tiene su propia
 * implementación. Agregar un nuevo modelo = crear nueva clase sin tocar nada.
 *
 * Métodos model-specific: uploadFile, saveLink
 * Métodos model-agnostic (implementados en AbstractFileService): makePublic, etc.
 */
interface FileStorageContract
{
    /**
     * Sube un archivo físico al almacenamiento y crea el registro en BD.
     *
     * @param UploadedFile $file        Archivo recibido del request
     * @param int          $usuarioId   Usuario que sube el archivo
     * @param int          $procesoId   Proceso al que pertenece
     * @param int          $referenciaId evidencia_id o elemento_id según modelo
     */
    public function uploadFile(
        UploadedFile $file,
        int $usuarioId,
        int $procesoId,
        int $referenciaId
    ): File;

    /**
     * Guarda una URL/enlace como evidencia en la base de datos.
     *
     * @param string      $url              URL a guardar
     * @param int         $usuarioId        Usuario que guarda el enlace
     * @param int         $procesoId        Proceso al que pertenece
     * @param int         $referenciaId     evidencia_id o elemento_id según modelo
     * @param string|null $nombreDescriptivo Nombre legible para el enlace
     */
    public function saveLink(
        string $url,
        int $usuarioId,
        int $procesoId,
        int $referenciaId,
        ?string $nombreDescriptivo = null
    ): File;

    /**
     * Hace público un archivo generando un token UUID y estableciendo expiración.
     */
    public function makePublic(File $archivo, ?\DateTime $expiresAt = null): File;

    /**
     * Revoca el acceso público de un archivo.
     */
    public function revokePublicAccess(File $archivo): File;

    /**
     * Elimina un archivo del almacenamiento y de la base de datos.
     */
    public function deleteFile(File $archivo): bool;

    /**
     * Hace públicos múltiples archivos masivamente.
     *
     * @param  int[]          $archivosIds
     * @return File[]
     */
    public function bulkMakePublic(array $archivosIds, ?\DateTime $expiresAt = null): array;

    /**
     * Retorna el nombre del disco de almacenamiento configurado.
     */
    public function getDisk(): string;

    /**
     * Genera la URL pública de acceso para un archivo con token activo.
     */
    public function getPublicUrl(File $archivo): ?string;

    /**
     * Indica si el archivo físico existe en el disco.
     */
    public function fileExists(File $archivo): bool;

    public function getFileSize(File $archivo): int;
    public function getMimeType(File $archivo): ?string;
}
