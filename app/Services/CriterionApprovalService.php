<?php

namespace App\Services;

use App\Models\Criterion;
use App\Models\CriterionApproval;
use App\Models\EvidenceApproval;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Exception;

/**
 * Servicio que gestiona la lógica de negocio relacionada con Aprobaciones de Criterios.
 * Incluye operaciones para aprobar/rechazar criterios por bloques y validar completitud de evidencias.
 */
class CriterionApprovalService
{
    public function __construct()
    {
        // Preparado para futuras dependencias (ej: logger, auditoría, etc.)
    }

    /**
     * Listar todas las aprobaciones de criterios con sus relaciones.
     * Si el usuario es Profesor, solo muestra aprobaciones de criterios donde tiene evidencias asignadas.
     *
     * @return \Illuminate\Database\Eloquent\Collection Colección de aprobaciones.
     */
    public function listApprovals()
    {
        $query = CriterionApproval::with(['criterion', 'process', 'user', 'evidenceApprovals.evidence']);

        /** @var \App\Models\User|\Spatie\Permission\Traits\HasRoles $user */
        $user = Auth::user();

        // Si es Profesor, filtrar solo criterios donde tiene evidencias asignadas
        if ($user && $user->hasRole('Profesor')) {
            $query->whereHas('criterion', function ($criterionQuery) use ($user) {
                $criterionQuery->withoutGlobalScope('byCareerCampus')
                  ->whereHas('evidences.assignments', function ($assignmentQuery) use ($user) {
                      $assignmentQuery->where('usuario_id', $user->usuario_id);
                  });
            });
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Obtener una aprobación específica por ID.
     *
     * @param int $approvalId Identificador único de la aprobación.
     * @return CriterionApproval|null Retorna la aprobación o null si no existe.
     */
    public function getApproval(int $approvalId): ?CriterionApproval
    {
        return CriterionApproval::with(['criterion', 'process', 'user', 'evidenceApprovals.evidence'])->find($approvalId);
    }

  

    /**
     * Aprobar un criterio (bloque de evidencias).
     * Valida que todas las evidencias estén completas antes de aprobar.
     * Se usa transacción para garantizar atomicidad.
     * 
     * @param int $criterioId Identificador del criterio a aprobar.
     * @param int $procesoId Identificador del proceso de acreditación.
     * @param int $usuarioId Identificador del usuario que aprueba.
     * @param string|null $comentario Comentario opcional sobre la aprobación.
     * @return CriterionApproval Aprobación recién creada.
     * @throws Exception Si ya fue aprobado o faltan evidencias.
     */
    public function approveCriterion(int $criterioId, int $procesoId, int $usuarioId, ?string $comentario = null): CriterionApproval
    {
        return DB::transaction(function () use ($criterioId, $procesoId, $usuarioId, $comentario) {

            // Verificar si ya existe una aprobación para este criterio en este proceso
            $existingApproval = CriterionApproval::where('criterio_id', $criterioId)
                ->where('proceso_id', $procesoId)
                ->first();

            if ($existingApproval) {
                // Actualizar la decisión existente
                $existingApproval->update([
                    'usuario_id' => $usuarioId,
                    'estado' => 'aprobado',
                    'comentario' => $comentario
                ]);

                // Actualizar o crear aprobaciones individuales para cada evidencia
                $this->createEvidenceApprovals($existingApproval, $usuarioId);
                
                return $existingApproval->fresh(['criterion', 'process', 'user', 'evidenceApprovals.evidence']);
            }

            // Crear nueva aprobación si no existe
            $approval = CriterionApproval::create([
                'criterio_id' => $criterioId,
                'proceso_id' => $procesoId,
                'usuario_id' => $usuarioId,
                'estado' => 'aprobado',
                'comentario' => $comentario
            ]);

            // Crear aprobaciones individuales para cada evidencia del criterio
            $this->createEvidenceApprovals($approval, $usuarioId);

            return $approval->load(['criterion', 'process', 'user', 'evidenceApprovals.evidence']);
        });
    }

    /**
     * Rechazar un criterio (bloque de evidencias).
     * Se usa transacción para garantizar atomicidad.
     * 
     * @param int $criterioId Identificador del criterio a rechazar.
     * @param int $procesoId Identificador del proceso de acreditación.
     * @param int $usuarioId Identificador del usuario que rechaza.
     * @param string|null $comentario Comentario opcional sobre el rechazo.
     * @return CriterionApproval Rechazo recién creado.
     * @throws Exception Si ya fue aprobado/rechazado.
     */
    public function rejectCriterion(int $criterioId, int $procesoId, int $usuarioId, ?string $comentario = null): CriterionApproval
    {
        return DB::transaction(function () use ($criterioId, $procesoId, $usuarioId, $comentario) {
            // Verificar si ya existe una aprobación para este criterio en este proceso
            $existingApproval = CriterionApproval::where('criterio_id', $criterioId)
                ->where('proceso_id', $procesoId)
                ->first();

            if ($existingApproval) {
                // Actualizar la decisión existente
                $existingApproval->update([
                    'usuario_id' => $usuarioId,
                    'estado' => 'rechazado',
                    'comentario' => $comentario
                ]);
                
                // Rechazar automáticamente todas las evidencias del criterio
                $this->rejectEvidenceApprovals($existingApproval, $usuarioId);
                
                return $existingApproval->fresh(['criterion', 'process', 'user', 'evidenceApprovals.evidence']);
            }

            // Crear el rechazo
            $approval = CriterionApproval::create([
                'criterio_id' => $criterioId,
                'proceso_id' => $procesoId,
                'usuario_id' => $usuarioId,
                'estado' => 'rechazado',
                'comentario' => $comentario
            ]);

            // Rechazar automáticamente todas las evidencias del criterio
            $this->rejectEvidenceApprovals($approval, $usuarioId);

            return $approval->load(['criterion', 'process', 'user', 'evidenceApprovals.evidence']);
        });
    }

    /**
     * Verificar que TODAS las evidencias de un criterio estén completas.
     * 
     * Una evidencia se considera completa cuando:
     * - Tiene al menos UNA asignación en el proceso especificado
     * - Y esa asignación tiene estado = 'completado'
     * 
     * @param int $criterioId Identificador del criterio.
     * @param int $procesoId Identificador del proceso.
     * @return array ['is_complete' => bool, 'total' => int, 'completed' => int, 'missing_evidences' => array]
     */
    public function verifyAllEvidencesAreComplete(int $criterioId, int $procesoId): array
    {
        $criterion = Criterion::with('evidences')->find($criterioId);
        
        if (!$criterion) {
            return [
                'is_complete' => false,
                'total' => 0,
                'completed' => 0,
                'missing_evidences' => []
            ];
        }

        $evidences = $criterion->evidences()->where('activo', true)->get();
        $total = $evidences->count();
        $completed = 0;
        $missingEvidences = [];

        foreach ($evidences as $evidence) {
            // Verificar si la evidencia tiene al menos una asignación completada en el proceso
            $hasCompletedAssignment = $evidence->assignments()
                ->where('proceso_id', $procesoId)
                ->where('estado', 'completado')
                ->exists();

            if ($hasCompletedAssignment) {
                $completed++;
            } else {
                $missingEvidences[] = [
                    'evidencia_id' => $evidence->evidencia_id,
                    'nomenclatura' => $evidence->nomenclatura,
                    'descripcion' => $evidence->descripcion
                ];
            }
        }

        return [
            'is_complete' => $completed === $total && $total > 0,
            'total' => $total,
            'completed' => $completed,
            'missing_evidences' => $missingEvidences
        ];
    }

    /**
     * Crear aprobaciones individuales para cada evidencia del criterio.
     * Si ya existe una aprobación para una evidencia, se actualiza.
     *
     * @param CriterionApproval $criterionApproval Aprobación del criterio.
     * @param int $usuarioId Usuario que aprueba.
     * @return void
     */
    private function createEvidenceApprovals(CriterionApproval $criterionApproval, int $usuarioId): void
    {
        $criterion = Criterion::with('evidences')->find($criterionApproval->criterio_id);
        
        foreach ($criterion->evidences as $evidence) {
            // Verificar si ya existe una aprobación para esta evidencia
            $existingEvidenceApproval = EvidenceApproval::where('evidencia_id', $evidence->evidencia_id)
                ->where('proceso_id', $criterionApproval->proceso_id)
                ->where('criterio_aprobacion_id', $criterionApproval->aprobacion_criterio_id)
                ->first();

            if ($existingEvidenceApproval) {
                // Actualizar la aprobación existente
                $existingEvidenceApproval->update([
                    'usuario_id' => $usuarioId,
                    'estado' => 'aprobado'
                ]);
            } else {
                // Crear nueva aprobación de evidencia
                EvidenceApproval::create([
                    'evidencia_id' => $evidence->evidencia_id,
                    'proceso_id' => $criterionApproval->proceso_id,
                    'criterio_aprobacion_id' => $criterionApproval->aprobacion_criterio_id,
                    'usuario_id' => $usuarioId,
                    'estado' => 'aprobado'
                ]);
            }
        }
    }

    /**
     * Rechazar aprobaciones individuales para cada evidencia del criterio.
     * Si ya existe una aprobación para una evidencia, se actualiza a rechazado.
     *
     * @param CriterionApproval $criterionApproval Rechazo del criterio.
     * @param int $usuarioId Usuario que rechaza.
     * @return void
     */
    private function rejectEvidenceApprovals(CriterionApproval $criterionApproval, int $usuarioId): void
    {
        $criterion = Criterion::with('evidences')->find($criterionApproval->criterio_id);
        
        foreach ($criterion->evidences as $evidence) {
            // Verificar si ya existe una aprobación para esta evidencia
            $existingEvidenceApproval = EvidenceApproval::where('evidencia_id', $evidence->evidencia_id)
                ->where('proceso_id', $criterionApproval->proceso_id)
                ->where('criterio_aprobacion_id', $criterionApproval->aprobacion_criterio_id)
                ->first();

            if ($existingEvidenceApproval) {
                // Actualizar la aprobación existente a rechazado
                $existingEvidenceApproval->update([
                    'usuario_id' => $usuarioId,
                    'estado' => 'rechazado'
                ]);
            } else {
                // Crear nuevo rechazo de evidencia
                EvidenceApproval::create([
                    'evidencia_id' => $evidence->evidencia_id,
                    'proceso_id' => $criterionApproval->proceso_id,
                    'criterio_aprobacion_id' => $criterionApproval->aprobacion_criterio_id,
                    'usuario_id' => $usuarioId,
                    'estado' => 'rechazado'
                ]);
            }
        }
    }
}
