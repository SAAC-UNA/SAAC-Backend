<?php

namespace App\Services;

use App\Models\ElementAssignment;
use App\Models\File;
use App\Models\StructureElement;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Estrategia de almacenamiento para el Modelo Flexible (Arquitectura B - Flujo 2).
 *
 * Responsabilidad única: gestionar archivos cuya referencia es un ELEMENTO
 * del modelo de acreditación flexible (SINAES extendido).
 *
 * - Columna de referencia: elemento_id
 * - evidencia_id siempre null
 * - Al subir, pasa ElementAssignment de Pendiente → En Progreso
 */
class FlexibleFileService extends AbstractFileService
{
    public function uploadFile(
        UploadedFile $file,
        int $usuarioId,
        int $procesoId,
        int $referenciaId
    ): File {
        $this->validarTipoAsignable($referenciaId);

        $extension = $file->getClientOriginalExtension();
        $uuid      = (string) Str::uuid();
        $filename  = "{$uuid}.{$extension}";

        return DB::transaction(function () use ($file, $filename, $referenciaId, $usuarioId, $procesoId) {
            $path = Storage::disk($this->disk)->putFileAs('', $file, $filename);

            $archivo = File::create([
                'evidencia_id'    => null,
                'elemento_id'     => $referenciaId,
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

            Log::info('[Flexible] Archivo subido exitosamente', [
                'archivo_id'      => $archivo->archivo_id,
                'nombre_original' => $archivo->nombre_original,
                'usuario_id'      => $usuarioId,
                'elemento_id'     => $referenciaId,
                'size_kb'         => round($file->getSize() / 1024, 2),
                'disk_free_gb'    => round($diskFreeSpace / (1024 ** 3), 2),
            ]);

            $this->marcarEnProgreso($referenciaId, $usuarioId, $procesoId);
            $this->clearAssignmentCaches($referenciaId, $usuarioId, $procesoId);

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
        $this->validarTipoAsignable($referenciaId);

        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new \InvalidArgumentException('URL invalida: ' . $url);
        }

        if (!$nombreDescriptivo) {
            $parsedUrl         = parse_url($url);
            $nombreDescriptivo = $parsedUrl['host'] ?? 'Enlace';
        }

        return DB::transaction(function () use ($url, $referenciaId, $usuarioId, $procesoId, $nombreDescriptivo) {
            $enlace = File::create([
                'evidencia_id'    => null,
                'elemento_id'     => $referenciaId,
                'usuario_id'      => $usuarioId,
                'proceso_id'      => $procesoId,
                'fecha_subida'    => now(),
                'tipo'            => 'enlace',
                'path'            => null,
                'url'             => $url,
                'nombre_original' => $nombreDescriptivo,
                'is_publico'      => false,
            ]);

            Log::info('[Flexible] Enlace guardado exitosamente', [
                'archivo_id'         => $enlace->archivo_id,
                'url'                => $url,
                'nombre_descriptivo' => $nombreDescriptivo,
                'usuario_id'         => $usuarioId,
                'elemento_id'        => $referenciaId,
            ]);

            $this->marcarEnProgreso($referenciaId, $usuarioId, $procesoId);
            $this->clearAssignmentCaches($referenciaId, $usuarioId, $procesoId);

            return $enlace;
        });
    }

    private function clearAssignmentCaches(int $elementoId, int $usuarioId, int $procesoId): void
    {
        Cache::forget('element-assignments.all');
        Cache::forget("element-assignments.user.{$usuarioId}");
        Cache::forget("element-assignments.element.{$elementoId}");
        Cache::forget("element-assignments.process.{$procesoId}");
        Cache::forget("element-assignments.element.{$elementoId}.process.{$procesoId}");
    }

    /**
     * Pasa los ElementAssignments del usuario/proceso en 'Pendiente' → 'En Progreso'.
     * Se usa cada() para disparar el observer de ElementAssignment si existe.
     */
    protected function marcarEnProgreso(int $referenciaId, int $usuarioId, int $procesoId): void
    {
        ElementAssignment::where('elemento_id', $referenciaId)
            ->where('usuario_id', $usuarioId)
            ->where('proceso_id', $procesoId)
            ->where('estado', 'Pendiente')
            ->get()
            ->each(fn($assignment) => $assignment->update(['estado' => 'En Progreso']));
    }

    /**
     * Guard Estrategia 3: el modelo define qué tipos de nodo pueden recibir archivos.
     * Si tipos_asignables está vacío/null no se restringe (retrocompatibilidad).
     */
    private function validarTipoAsignable(int $elementoId): void
    {
        $elemento = StructureElement::find($elementoId);
        if (!$elemento) {
            throw new \InvalidArgumentException("El elemento {$elementoId} no existe.");
        }

        $tiposAsignables = optional($elemento->structureModel)->tipos_asignables;
        if (!empty($tiposAsignables) && !in_array($elemento->tipo, $tiposAsignables)) {
            throw new \InvalidArgumentException(
                "El elemento de tipo '{$elemento->tipo}' no acepta archivos en este modelo. " .
                'Tipos permitidos: ' . implode(', ', $tiposAsignables) . '.'
            );
        }
    }
}
