<?php

namespace App\Services;

use App\Models\EvidenceAssignment;
use App\Models\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Estrategia de almacenamiento para el Modelo Tradicional (Arquitectura B - Flujo 1).
 *
 * Responsabilidad única: gestionar archivos cuya referencia es una EVIDENCIA
 * del modelo de acreditación tradicional (SINAES estándar).
 *
 * - Columna de referencia: evidencia_id
 * - elemento_id siempre null
 * - Al subir, pasa EvidenceAssignment de Pendiente → En Progreso
 */
class TradicionalFileService extends AbstractFileService
{
    public function uploadFile(
        UploadedFile $file,
        int $usuarioId,
        int $procesoId,
        int $referenciaId
    ): File {
        $extension = $file->getClientOriginalExtension();
        $uuid      = (string) Str::uuid();
        $filename  = "{$uuid}.{$extension}";

        return DB::transaction(function () use ($file, $filename, $referenciaId, $usuarioId, $procesoId) {
            $path = Storage::disk($this->disk)->putFileAs('', $file, $filename);

            $archivo = File::create([
                'evidencia_id'    => $referenciaId,
                'elemento_id'     => null,
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

            Log::info('[Tradicional] Archivo subido exitosamente', [
                'archivo_id'      => $archivo->archivo_id,
                'nombre_original' => $archivo->nombre_original,
                'usuario_id'      => $usuarioId,
                'evidencia_id'    => $referenciaId,
                'size_kb'         => round($file->getSize() / 1024, 2),
                'disk_free_gb'    => round($diskFreeSpace / (1024 ** 3), 2),
            ]);

            $this->marcarEnProgreso($referenciaId, $usuarioId, $procesoId);

            return $archivo;
        });
    }

    public function saveLink(
        string $url,
        int $usuarioId,
        int $procesoId,
        int $referenciaId,
        ?string $nombreDescriptivo = null
    ): File {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new \InvalidArgumentException('URL invalida: ' . $url);
        }

        if (!$nombreDescriptivo) {
            $parsedUrl         = parse_url($url);
            $nombreDescriptivo = $parsedUrl['host'] ?? 'Enlace';
        }

        return DB::transaction(function () use ($url, $referenciaId, $usuarioId, $procesoId, $nombreDescriptivo) {
            $enlace = File::create([
                'evidencia_id'    => $referenciaId,
                'elemento_id'     => null,
                'usuario_id'      => $usuarioId,
                'proceso_id'      => $procesoId,
                'fecha_subida'    => now(),
                'tipo'            => 'enlace',
                'path'            => null,
                'url'             => $url,
                'nombre_original' => $nombreDescriptivo,
                'is_publico'      => false,
            ]);

            Log::info('[Tradicional] Enlace guardado exitosamente', [
                'archivo_id'         => $enlace->archivo_id,
                'url'                => $url,
                'nombre_descriptivo' => $nombreDescriptivo,
                'usuario_id'         => $usuarioId,
                'evidencia_id'       => $referenciaId,
            ]);

            $this->marcarEnProgreso($referenciaId, $usuarioId, $procesoId);

            return $enlace;
        });
    }

    /**
     * Si la asignación del usuario para esta evidencia está en 'Pendiente',
     * la pasa a 'En Progreso'. El EvidenceAssignmentObserver propaga el cambio
     * hacia EVIDENCIA.estado y luego hacia CRITERIO.estado automáticamente.
     */
    protected function marcarEnProgreso(int $referenciaId, int $usuarioId, int $procesoId): void
    {
        EvidenceAssignment::where('evidencia_id', $referenciaId)
            ->where('usuario_id', $usuarioId)
            ->where('proceso_id', $procesoId)
            ->where('estado', EvidenceAssignment::ESTADO_PENDIENTE)
            ->get()
            ->each(fn (EvidenceAssignment $assignment) => $assignment->update([
                'estado' => EvidenceAssignment::ESTADO_EN_PROGRESO,
            ]));
    }
}
