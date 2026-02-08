<?php

namespace App\Services;

use App\Models\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class FileService
{
    /**
     * Disco de almacenamiento configurado.
     */
    private string $disk = 'simulated_nas';

    /**
     * Obtener el disco de almacenamiento configurado.
     */
    public function getDisk(): string
    {
        return $this->disk;
    }

    /**
     * Sube un archivo al almacenamiento y crea el registro en la base de datos.
     * 
     * @param UploadedFile $file Archivo subido
     * @param int $evidenciaId ID de la evidencia asociada
     * @param int $usuarioId ID del usuario que sube el archivo
     * @param int $procesoId ID del proceso asociado
     * @return File Modelo del archivo creado
     */
    public function uploadFile(
        UploadedFile $file,
        int $evidenciaId,
        int $usuarioId,
        int $procesoId
    ): File {
        // Generar nombre único usando UUID para evitar colisiones y path traversal
        $extension = $file->getClientOriginalExtension();
        $uuid = (string) Str::uuid();
        $filename = "{$uuid}.{$extension}";

        // Envolverse en transacción para garantizar integridad
        // Si la base de datos falla, el archivo no se guarda
        return DB::transaction(function () use ($file, $extension, $uuid, $filename, $evidenciaId, $usuarioId, $procesoId) {
            // Guardar archivo en el disco configurado
            $path = Storage::disk($this->disk)->putFileAs('', $file, $filename);

            // Crear registro en la base de datos
            $archivo = File::create([
                'evidencia_id' => $evidenciaId,
                'usuario_id' => $usuarioId,
                'proceso_id' => $procesoId,
                'fecha_subida' => now(),
                'tipo' => 'archivo', // Tipo: archivo físico
                'path' => $path,
                'url' => null, // No hay URL para archivos físicos
                'nombre_original' => $file->getClientOriginalName(),
                'is_publico' => false, // Privado por defecto
                'token_publico' => null,
                'link_expira_en' => null,
            ]);

            // Log de espacio en disco después de guardar
            $diskFreeSpace = disk_free_path(Storage::disk($this->disk)->path(''));
            $diskFreeGb = round($diskFreeSpace / (1024 ** 3), 2);

            Log::info('Archivo subido exitosamente', [
                'archivo_id' => $archivo->archivo_id,
                'nombre_original' => $archivo->nombre_original,
                'usuario_id' => $usuarioId,
                'evidencia_id' => $evidenciaId,
                'size_kb' => round($file->getSize() / 1024, 2),
                'disk_free_gb' => $diskFreeGb,
            ]);

            return $archivo;
        });
    }

    /**
     * Guarda un enlace/URL como evidencia en la base de datos.
     * 
     * @param string $url URL del enlace
     * @param int $evidenciaId ID de la evidencia asociada
     * @param int $usuarioId ID del usuario que guarda el enlace
     * @param int $procesoId ID del proceso asociado
     * @param string|null $nombreDescriptivo Nombre descriptivo opcional
     * @return File Modelo del enlace creado
     */
    public function saveLink(
        string $url,
        int $evidenciaId,
        int $usuarioId,
        int $procesoId,
        ?string $nombreDescriptivo = null
    ): File {
        // Validar que la URL sea válida
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new \InvalidArgumentException('URL inválida: ' . $url);
        }
        
        // Extraer nombre del dominio si no se proporciona nombre descriptivo
        if (!$nombreDescriptivo) {
            $parsedUrl = parse_url($url);
            $nombreDescriptivo = ($parsedUrl['host'] ?? 'Enlace') . ' - ' . date('Y-m-d H:i:s');
        }
        
        return DB::transaction(function () use ($url, $evidenciaId, $usuarioId, $procesoId, $nombreDescriptivo) {
            // Crear registro en la base de datos
            $enlace = File::create([
                'evidencia_id' => $evidenciaId,
                'usuario_id' => $usuarioId,
                'proceso_id' => $procesoId,
                'fecha_subida' => now(),
                'tipo' => 'enlace',
                'url' => $url,
                'path' => null, // No hay archivo físico
                'nombre_original' => $nombreDescriptivo,
                'is_publico' => false,
                'token_publico' => null,
                'link_expira_en' => null,
            ]);
            
            Log::info('Enlace guardado exitosamente', [
                'archivo_id' => $enlace->archivo_id,
                'url' => $url,
                'nombre_descriptivo' => $nombreDescriptivo,
                'usuario_id' => $usuarioId,
                'evidencia_id' => $evidenciaId,
            ]);
            
            return $enlace;
        });
    }

    /**
     * Hace público un archivo generando un token UUID y estableciendo expiración.
     * 
     * @param File $archivo Archivo a hacer público
     * @param \DateTime|null $expiresAt Fecha de expiración (default: 1 año)
     * @return File Archivo actualizado
     */
    public function makePublic(File $archivo, ?\DateTime $expiresAt = null): File
    {
        $archivo->is_publico = true;
        $archivo->token_publico = (string) Str::uuid();
        $archivo->link_expira_en = $expiresAt ?? now()->addYear();
        $archivo->save();

        Log::info('Archivo marcado como público', [
            'archivo_id' => $archivo->archivo_id,
            'token_publico' => $archivo->token_publico,
            'expira_en' => $archivo->link_expira_en,
        ]);

        return $archivo;
    }

    /**
     * Revoca el acceso público de un archivo.
     * 
     * @param File $archivo Archivo a revocar acceso
     * @return File Archivo actualizado
     */
    public function revokePublicAccess(File $archivo): File
    {
        $archivo->is_publico = false;
        $archivo->token_publico = null;
        $archivo->link_expira_en = null;
        $archivo->save();

        Log::info('Acceso público revocado', [
            'archivo_id' => $archivo->archivo_id,
        ]);

        return $archivo;
    }

    /**
     * Elimina un archivo del almacenamiento y de la base de datos.
     * Para enlaces, solo elimina el registro de BD (no hay archivo físico).
     * 
     * @param File $archivo Archivo o enlace a eliminar
     * @return bool True si se eliminó correctamente
     */
    public function deleteFile(File $archivo): bool
    {
        $path = $archivo->path;
        $archivoId = $archivo->archivo_id;
        $tipo = $archivo->tipo ?? 'archivo';

        // Solo eliminar archivo físico si NO es un enlace
        if ($tipo === 'archivo' && $path && Storage::disk($this->disk)->exists($path)) {
            Storage::disk($this->disk)->delete($path);
        }

        // Eliminar registro de la base de datos
        $deleted = $archivo->delete();

        Log::info($tipo === 'enlace' ? 'Enlace eliminado' : 'Archivo eliminado', [
            'archivo_id' => $archivoId,
            'tipo' => $tipo,
            'path' => $path,
            'url' => $tipo === 'enlace' ? $archivo->url : null,
        ]);

        return $deleted;
    }

    /**
     * Genera enlaces públicos masivamente para múltiples archivos.
     * 
     * @param array $archivosIds Array de IDs de archivos
     * @param \DateTime|null $expiresAt Fecha de expiración común
     * @return array Array de archivos actualizados
     */
    public function bulkMakePublic(array $archivosIds, ?\DateTime $expiresAt = null): array
    {
        $archivos = File::whereIn('archivo_id', $archivosIds)->get();
        $updated = [];

        foreach ($archivos as $archivo) {
            $updated[] = $this->makePublic($archivo, $expiresAt);
        }

        Log::info('Archivos marcados como públicos masivamente', [
            'cantidad' => count($updated),
            'archivos_ids' => $archivosIds,
        ]);

        return $updated;
    }

    /**
     * Obtiene la URL pública de un archivo si está disponible.
     * 
     * @param File $archivo Archivo
     * @return string|null URL pública o null si no está disponible
     */
    public function getPublicUrl(File $archivo): ?string
    {
        if (!$archivo->isPubliclyAccessible()) {
            return null;
        }

        return route('files.public', ['token' => $archivo->token_publico]);
    }

    /**
     * Valida si un archivo existe físicamente en el disco.
     * 
     * @param File $archivo Archivo a validar
     * @return bool True si existe
     */
    public function fileExists(File $archivo): bool
    {
        return Storage::disk($this->disk)->exists($archivo->path);
    }

    /**
     * Obtiene el tamaño de un archivo en bytes.
     * 
     * @param File $archivo Archivo
     * @return int Tamaño en bytes
     */
    public function getFileSize(File $archivo): int
    {
        if (!$this->fileExists($archivo)) {
            return 0;
        }

        return Storage::disk($this->disk)->size($archivo->path);
    }

    /**
     * Obtiene el tipo MIME de un archivo.
     * 
     * @param File $archivo Archivo
     * @return string|null Tipo MIME
     */
    public function getMimeType(File $archivo): ?string
    {
        if (!$this->fileExists($archivo)) {
            return null;
        }

        return Storage::disk($this->disk)->mimeType($archivo->path);
    }
}
