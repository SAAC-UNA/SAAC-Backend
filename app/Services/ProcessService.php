<?php

namespace App\Services;

use App\Models\Autoevaluation;
use App\Models\File;
use App\Models\ImprovementCommitment;
use App\Models\Process;
use App\Services\FileStorageFactory;
use Illuminate\Support\Facades\DB;

class ProcessService
{
    public function __construct(private readonly FileStorageFactory $factory) {}

    /**
     * Obtener todos los procesos con relaciones.
     */
    public function getAll(): \Illuminate\Database\Eloquent\Collection
    {
        return Process::with([
            'accreditationCycle.careerCampus.career',
            'accreditationCycle.careerCampus.campus',
            'accreditationCycle.structureModel',
        ])->get();
    }

    /**
     * Buscar proceso por ID. Lanza ModelNotFoundException si no existe.
     */
    public function findById(int $id): Process
    {
        return Process::findOrFail($id);
    }

    /**
     * Crear un nuevo proceso.
     */
    public function create(array $data): Process
    {
        return Process::create($data);
    }

    /**
     * Actualizar un proceso existente.
     */
    public function update(Process $process, array $data): Process
    {
        $process->update($data);
        return $process->fresh();
    }

    /**
     * Cambiar estado activo/inactivo de un proceso.
     * Valida que no haya conflicto con otro proceso activo del mismo tipo en el mismo ciclo.
     *
     * @throws \InvalidArgumentException si ya existe un proceso activo del mismo tipo en el ciclo
     */
    public function toggleActive(Process $process, bool $newState): Process
    {
        if ($newState) {
            $existsConflict = Process::where('ciclo_acreditacion_id', $process->ciclo_acreditacion_id)
                ->where('tipo_proceso', $process->tipo_proceso)
                ->where('activo', true)
                ->where('proceso_id', '!=', $process->proceso_id)
                ->exists();

            if ($existsConflict) {
                throw new \InvalidArgumentException(
                    'No se puede activar el proceso porque ya existe otro proceso activo del mismo tipo para este ciclo.'
                );
            }
        }

        $process->activo = $newState;
        $process->save();

        return $process;
    }

    /**
     * Obtener resumen de datos asociados que serían eliminados junto al proceso.
     */
    public function getDeleteSummary(Process $process): array
    {
        $asignacionIds = DB::table('EVIDENCIA_ASIGNACION')
            ->where('proceso_id', $process->proceso_id)
            ->pluck('evidencia_asignacion_id');

        return [
            'autoevaluaciones'       => Autoevaluation::where('proceso_id', $process->proceso_id)->count(),
            'compromisos_mejora'     => ImprovementCommitment::where('proceso_id', $process->proceso_id)->count(),
            'asignaciones_evidencia' => $asignacionIds->count(),
            'solicitudes_ampliacion' => $asignacionIds->isNotEmpty()
                ? DB::table('SOLICITUD_AMPLIACION')->whereIn('evidencia_asignacion_id', $asignacionIds)->count()
                : 0,
            'aprobaciones'           => DB::table('APROBACION_CRITERIO')->where('proceso_id', $process->proceso_id)->count(),
            'archivos'               => DB::table('ARCHIVO')->where('proceso_id', $process->proceso_id)->count(),
        ];
    }

    /**
     * Eliminar un proceso y todos sus datos dependientes.
     *
     * Orden manual necesario por restricciones FK de tipo restrict:
     *  1. ARCHIVO (proceso_id → restrict): borrar archivos físicos + registros
     *  2. SOLICITUD_AMPLIACION (evidencia_asignacion_id → restrict): borrar antes
     *     de que el cascade PROCESO→EVIDENCIA_ASIGNACION intente borrar las asignaciones
     *  3. $process->delete() — la BD maneja en cascade:
     *     AUTOEVALUACION, COMPROMISO_MEJORA (+pivots), EVIDENCIA_ASIGNACION,
     *     APROBACION_CRITERIO (+APROBACION_EVIDENCIA)
     */
    public function delete(Process $process): void
    {
        DB::transaction(function () use ($process) {
            // 1. Borrar archivos físicos + registros (restrict en proceso_id)
            File::where('proceso_id', $process->proceso_id)
                ->get()
                ->each(fn (File $archivo) => $this->factory->makeFromFile($archivo)->deleteFile($archivo));

            // 2. Borrar solicitudes de ampliación (restrict en evidencia_asignacion_id)
            $asignacionIds = DB::table('EVIDENCIA_ASIGNACION')
                ->where('proceso_id', $process->proceso_id)
                ->pluck('evidencia_asignacion_id');

            if ($asignacionIds->isNotEmpty()) {
                DB::table('SOLICITUD_AMPLIACION')
                    ->whereIn('evidencia_asignacion_id', $asignacionIds)
                    ->delete();
            }

            // 3. El resto cascadea automáticamente desde la BD
            $process->delete();
        });
    }
}
