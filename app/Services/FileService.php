<?php

namespace App\Services;

use App\Models\File;
use App\Models\EvidenceAssignment;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class FileService
{
    private string $disk;

    public function __construct()
    {
        $this->disk = config('saac.storage_disk', 'simulated_nas');
    }

    public function getDisk(): string
    {
        return $this->disk;
    }

    /**
     * Sube un archivo al almacenamiento y crea el registro en la base de datos.
     */
    public function uploadFile(
        UploadedFile $file,
        int $evidenciaId,
        int $usuarioId,
        int $procesoId
    ): File {
        $extension = $file->getClientOriginalExtension();
        $uuid      = (string) Str::uuid();
        $filename  = "{$uuid}.{$extension}";

        return DB::transaction(function () use ($file, $extension, $uuid, $filename, $evidenciaId, $usuarioId, $procesoId) {
            $path = Storage::disk($this->disk)->putFileAs('', $file, $filename);

            $archivo = File::create([
                'evidencia_id'    => $evidenciaId,
                'usuario_id'      => $usuarioId,
                'proceso_id'      => $procesoId,
                'fecha_subida'    => now(),
                'tipo'            => 'archivo',
                'path'            => $path,
                'url'             => null,
                'nombre_original' => $file->getClientOriginalName(),
                'tamanio'         => $file->getSize(),
                'tipo_mime'       => $file->getMimeType(),
                'is_publico'      => false,
            ]);

            $diskPath      = Storage::disk($this->disk)->path('');
            $diskFreeSpace = disk_free_space($diskPath);
            Log::info('Archivo subido exitosamente', [
                'archivo_id'      => $archivo->archivo_id,
                'nombre_original' => $archivo->nombre_original,
                'usuario_id'      => $usuarioId,
                'evidencia_id'    => $evidenciaId,
                'size_kb'         => round($file->getSize() / 1024, 2),
                'disk_free_gb'    => round($diskFreeSpace / (1024 ** 3), 2),
            ]);

            $this->marcarAsignacionEnProgreso($evidenciaId, $usuarioId, $procesoId);

            return $archivo;
        });
    }

    /**
     * Guarda un enlace/URL como evidencia en la base de datos.
     */
    public function saveLink(
        string $url,
        int $evidenciaId,
        int $usuarioId,
        int $procesoId,
        ?string $nombreDescriptivo = null
    ): File {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new \InvalidArgumentException('URL invalida: ' . $url);
        }

        if (!$nombreDescriptivo) {
            $parsedUrl          = parse_url($url);
            $nombreDescriptivo  = $parsedUrl['host'] ?? 'Enlace';
        }

        return DB::transaction(function () use ($url, $evidenciaId, $usuarioId, $procesoId, $nombreDescriptivo) {
            $enlace = File::create([
                'evidencia_id'    => $evidenciaId,
                'usuario_id'      => $usuarioId,
                'proceso_id'      => $procesoId,
                'fecha_subida'    => now(),
                'tipo'            => 'enlace',
                'path'            => null,
                'url'             => $url,
                'nombre_original' => $nombreDescriptivo,
                'is_publico'      => false,
            ]);

            Log::info('Enlace guardado exitosamente', [
                'archivo_id'        => $enlace->archivo_id,
                'url'               => $url,
                'nombre_descriptivo'=> $nombreDescriptivo,
                'usuario_id'        => $usuarioId,
                'evidencia_id'      => $evidenciaId,
            ]);

            $this->marcarAsignacionEnProgreso($evidenciaId, $usuarioId, $procesoId);

            return $enlace;
        });
    }

    /**
     * Hace publico un archivo generando un token UUID y estableciendo expiracion.
     */
    public function makePublic(File $archivo, ?\DateTime $expiresAt = null): File
    {
        $token     = (string) Str::uuid();
        $expiresAt = $expiresAt ?? now()->addYear()->toDateTime();
        $expira    = $expiresAt instanceof \DateTime ? $expiresAt->format('Y-m-d H:i:s') : $expiresAt;

        $archivo->update([
            'is_publico'     => true,
            'token_publico'  => $token,
            'link_expira_en' => $expira,
        ]);

        $archivo->refresh();

        Log::info('Archivo marcado como publico', [
            'archivo_id'    => $archivo->archivo_id,
            'token_publico' => $token,
            'expira_en'     => $expira,
        ]);

        return $archivo;
    }

    /**
     * Revoca el acceso publico de un archivo.
     */
    public function revokePublicAccess(File $archivo): File
    {
        $archivo->update([
            'is_publico'     => false,
            'token_publico'  => null,
            'link_expira_en' => null,
        ]);

        $archivo->refresh();

        Log::info('Acceso publico revocado', ['archivo_id' => $archivo->archivo_id]);

        return $archivo;
    }

    /**
     * Elimina un archivo del almacenamiento y de la base de datos.
     */
    public function deleteFile(File $archivo): bool
    {
        $path      = $archivo->path;
        $archivoId = $archivo->archivo_id;
        $tipo      = $archivo->tipo ?? 'archivo';

        if ($tipo === 'archivo' && $path && Storage::disk($this->disk)->exists($path)) {
            Storage::disk($this->disk)->delete($path);
        }

        $archivo->delete();

        Log::info($tipo === 'enlace' ? 'Enlace eliminado' : 'Archivo eliminado', [
            'archivo_id' => $archivoId,
            'tipo'       => $tipo,
            'path'       => $path,
        ]);

        return true;
    }

    /**
     * Genera enlaces publicos masivamente para multiples archivos.
     */
    public function bulkMakePublic(array $archivosIds, ?\DateTime $expiresAt = null): array
    {
        $updated = [];
        foreach ($archivosIds as $id) {
            $archivo = File::find($id);
            if ($archivo) {
                $updated[] = $this->makePublic($archivo, $expiresAt);
            }
        }

        Log::info('Archivos marcados como publicos masivamente', [
            'cantidad'     => count($updated),
            'archivos_ids' => $archivosIds,
        ]);

        return $updated;
    }

    public function getPublicUrl(File $archivo): ?string
    {
        if (!$archivo->isPubliclyAccessible()) {
            return null;
        }
        return route('files.public', ['token' => $archivo->token_publico]);
    }

    public function fileExists(File $archivo): bool
    {
        return Storage::disk($this->disk)->exists($archivo->path);
    }

    public function getFileSize(File $archivo): int
    {
        if (!$this->fileExists($archivo)) return 0;
        return Storage::disk($this->disk)->size($archivo->path);
    }

    public function getMimeType(File $archivo): ?string
    {
        if (!$this->fileExists($archivo)) return null;
        return Storage::disk($this->disk)->mimeType($archivo->path);
    }

    /**
     * Si la asignación del usuario para esta evidencia está en 'Pendiente',
     * la pasa a 'En Progreso'. El EvidenceAssignmentObserver propaga el cambio
     * hacia EVIDENCIA.estado y luego hacia CRITERIO.estado automáticamente.
     */
    private function marcarAsignacionEnProgreso(int $evidenciaId, int $usuarioId, int $procesoId): void
    {
        EvidenceAssignment::where('evidencia_id', $evidenciaId)
            ->where('usuario_id', $usuarioId)
            ->where('proceso_id', $procesoId)
            ->where('estado', EvidenceAssignment::ESTADO_PENDIENTE)
            ->update(['estado' => EvidenceAssignment::ESTADO_EN_PROGRESO]);
    }
}