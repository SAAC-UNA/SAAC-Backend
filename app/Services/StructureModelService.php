<?php

namespace App\Services;

use App\Models\AccreditationCycle;
use App\Models\File;
use App\Models\Process;
use App\Models\StructureElement;
use App\Models\StructureModel;
use App\Services\FileStorageFactory;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class StructureModelService
{
    public function __construct(private readonly FileStorageFactory $factory) {}

    /**
     * Obtener todos los modelos de estructura
     */
    public function getAll()
    {
        return Cache::remember('modelos_estructura.all', 300, function () {
            return StructureModel::orderBy('modelo_estructura_id')->get();
        });
    }

    /**
     * Obtener solo modelos activos
     */
    public function getActive()
    {
        return Cache::remember('modelos_estructura.active', 300, function () {
            return StructureModel::where('activo', true)->orderBy('modelo_estructura_id')->get();
        });
    }

    /**
     * Buscar modelo específico por ID
     */
    public function findById(int $id): ?StructureModel
    {
        return StructureModel::find($id);
    }

    /**
     * Crear nuevo modelo de estructura
     */
    public function create(array $data): StructureModel
    {
        $model = StructureModel::create($data);
        $this->clearCache();
        return $model;
    }

    /**
     * Actualizar metadata del modelo (nombre, descripcion, version)
     */
    public function update(StructureModel $model, array $data): StructureModel
    {
        $model->update($data);
        $this->clearCache();
        return $model->fresh();
    }

    /**
     * Activar/Desactivar modelo
     * 
     * NOTA IMPORTANTE (TODO - PENDIENTE):
     * ⚠️ Actualmente solo cambia el campo 'activo' del modelo, NO desactiva en cascada su estructura.
     * 
     * FALTA IMPLEMENTAR:
     * - Validar que no haya procesos activos antes de desactivar
     * - Desactivar en cascada la estructura asociada:
     *   * Si isTraditional(): DIMENSION → COMPONENTE → CRITERIO → ESTANDAR
     *   * Si isFlexibleElement(): ELEMENTO (WHERE modelo_estructura_id)
     * - NO desactivar EVIDENCIA (las evidencias deben permanecer activas)
     * 
     * Referencia código pendiente:
     * if ($model->activo && !$newState) {
     *     // Validar procesos activos
     *     if ($model->accreditationCycles()->whereHas('processes', fn($q) => $q->where('activo', true))->exists()) {
     *         throw new \Exception('No se puede desactivar - tiene procesos activos');
     *     }
     *     // Desactivar estructura
     *     if ($model->isTraditional()) {
     *         DB::table('DIMENSION')->update(['activo' => false]);
     *         DB::table('COMPONENTE')->update(['activo' => false]);
     *         DB::table('CRITERIO')->update(['activo' => false]);
     *         DB::table('ESTANDAR')->update(['activo' => false]);
     *     } elseif ($model->isFlexibleElement()) {
     *         DB::table('ELEMENTO')->where('modelo_estructura_id', $model->modelo_estructura_id)->update(['activo' => false]);
     *     }
     * }
     */
    public function toggleActive(StructureModel $model): StructureModel
    {
        $model->activo = !$model->activo;
        $model->save();
        $this->clearCache();
        return $model;
    }

    /**
     * Resumen completo de todo lo que será eliminado junto al modelo.
     * Incluye ciclos, procesos y toda la cadena de datos dependientes.
     */
    public function getDeleteSummary(StructureModel $model): array
    {
        $cicloIds = AccreditationCycle::where('modelo_estructura_id', $model->modelo_estructura_id)
            ->pluck('ciclo_acreditacion_id');

        $procesoIds = Process::whereIn('ciclo_acreditacion_id', $cicloIds)
            ->pluck('proceso_id');

        $asignacionIds = $procesoIds->isNotEmpty()
            ? DB::table('EVIDENCIA_ASIGNACION')->whereIn('proceso_id', $procesoIds)->pluck('evidencia_asignacion_id')
            : collect();

        return [
            'ciclos_acreditacion'    => $cicloIds->count(),
            'procesos'               => $procesoIds->count(),
            'autoevaluaciones'       => $procesoIds->isNotEmpty() ? DB::table('AUTOEVALUACION')->whereIn('proceso_id', $procesoIds)->count() : 0,
            'compromisos_mejora'     => $procesoIds->isNotEmpty() ? DB::table('COMPROMISO_MEJORA')->whereIn('proceso_id', $procesoIds)->count() : 0,
            'asignaciones_evidencia' => $asignacionIds->count(),
            'solicitudes_ampliacion' => $asignacionIds->isNotEmpty() ? DB::table('SOLICITUD_AMPLIACION')->whereIn('evidencia_asignacion_id', $asignacionIds)->count() : 0,
            'aprobaciones'           => $procesoIds->isNotEmpty() ? DB::table('APROBACION_CRITERIO')->whereIn('proceso_id', $procesoIds)->count() : 0,
            'archivos'               => $procesoIds->isNotEmpty() ? DB::table('ARCHIVO')->whereIn('proceso_id', $procesoIds)->count() : 0,
            'elementos'              => $model->isFlexibleElement() ? StructureElement::where('modelo_estructura_id', $model->modelo_estructura_id)->count() : 0,
        ];
    }

    /**
     * Eliminar el modelo y todo lo que depende de él en cascada:
     * ciclos → procesos (archivos físicos + solicitudes manualmente) → Elements → modelo.
     *
     * @return array Resumen de registros eliminados
     */
    public function delete(StructureModel $model): array
    {
        $summary = $this->getDeleteSummary($model);

        DB::transaction(function () use ($model) {
            $cicloIds = AccreditationCycle::where('modelo_estructura_id', $model->modelo_estructura_id)
                ->pluck('ciclo_acreditacion_id');

            if ($cicloIds->isNotEmpty()) {
                $procesoIds = Process::whereIn('ciclo_acreditacion_id', $cicloIds)
                    ->pluck('proceso_id');

                if ($procesoIds->isNotEmpty()) {
                    // 1. Borrar archivos físicos + registros (restrict en proceso_id)
                    File::whereIn('proceso_id', $procesoIds)
                        ->get()
                        ->each(fn (File $archivo) => $this->factory->makeFromFile($archivo)->deleteFile($archivo));

                    // 2. Borrar solicitudes de ampliación (restrict en evidencia_asignacion_id)
                    $asignacionIds = DB::table('EVIDENCIA_ASIGNACION')
                        ->whereIn('proceso_id', $procesoIds)
                        ->pluck('evidencia_asignacion_id');

                    if ($asignacionIds->isNotEmpty()) {
                        DB::table('SOLICITUD_AMPLIACION')
                            ->whereIn('evidencia_asignacion_id', $asignacionIds)
                            ->delete();
                    }

                    // 3. Borrar procesos — cascade BD maneja: AUTOEVALUACION, COMPROMISO_MEJORA,
                    //    EVIDENCIA_ASIGNACION, APROBACION_CRITERIO, APROBACION_EVIDENCIA
                    Process::whereIn('proceso_id', $procesoIds)->delete();
                }

                // 4. Borrar ciclos (PROCESO ya fue eliminado, sin restrict)
                AccreditationCycle::whereIn('ciclo_acreditacion_id', $cicloIds)->delete();
            }

            // 5. Borrar Elements (nullify padre_id para FK auto-referenciada)
            if ($model->isFlexibleElement()) {
                DB::table('ELEMENTO')
                    ->where('modelo_estructura_id', $model->modelo_estructura_id)
                    ->update(['padre_id' => null]);

                DB::table('ELEMENTO')
                    ->where('modelo_estructura_id', $model->modelo_estructura_id)
                    ->delete();
            }

            // 6. Eliminar el modelo
            $model->delete();
        });

        $this->clearCache();

        return $summary;
    }

    /**
     * Limpiar caché de modelos de estructura
     */
    private function clearCache(): void
    {
        Cache::forget('modelos_estructura.all');
        Cache::forget('modelos_estructura.active');
    }
}
