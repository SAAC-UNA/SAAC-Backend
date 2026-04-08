<?php

namespace App\Services;

use App\Contracts\FileStorageContract;
use App\Models\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Implementaciones compartidas entre todos los modelos de acreditación.
 *
 * makePublic, revokePublicAccess, deleteFile, bulkMakePublic, getDisk, etc.
 * son idénticas sin importar el modelo → viven aquí una sola vez (DRY + SRP).
 *
 * Las subclases solo implementan lo que ES diferente por modelo:
 * uploadFile, saveLink, marcarEnProgreso.
 */
abstract class AbstractFileService implements FileStorageContract
{
    protected string $disk;

    public function __construct()
    {
        $this->disk = config('saac.storage_disk', 'simulated_nas');
    }

    public function getDisk(): string
    {
        return $this->disk;
    }

    // -----------------------------------------------------------------------
    // Operaciones model-agnostic (implementadas aquí, no en las subclases)
    // -----------------------------------------------------------------------

    public function makePublic(File $archivo, ?\DateTime $expiresAt = null): File
    {
        $token     = (string) Str::uuid();
        $expiresAt = $expiresAt ?? now()->addYear()->toDateTime();
        $expira    = $expiresAt instanceof \DateTime
            ? $expiresAt->format('Y-m-d H:i:s')
            : $expiresAt;

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
        $baseUrl = (string) config('app.frontend_url', config('app.url'));

        return rtrim($baseUrl, '/') . '/p/' . $archivo->token_publico;
    }

    public function fileExists(File $archivo): bool
    {
        return Storage::disk($this->disk)->exists($archivo->path);
    }

    public function getFileSize(File $archivo): int
    {
        if (!$this->fileExists($archivo)) {
            return 0;
        }
        return Storage::disk($this->disk)->size($archivo->path);
    }

    public function getMimeType(File $archivo): ?string
    {
        if (!$this->fileExists($archivo)) {
            return null;
        }
        /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
        $disk = Storage::disk($this->disk);
        return $disk->mimeType($archivo->path);
    }

    // -----------------------------------------------------------------------
    // Cada subclase implementa cómo pasar el estado a "En Progreso"
    // -----------------------------------------------------------------------

    abstract protected function marcarEnProgreso(int $referenciaId, int $usuarioId, int $procesoId): void;
}
