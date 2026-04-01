<?php

namespace App\Services;

use App\Models\ElementApproval;
use App\Models\StructureElement;
use App\Events\ElementApproved;
use App\Events\ElementRejected;
use App\Services\AuditLogService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

/**
 * Servicio de aprobación de bloques — modelo flexible (ELEMENTO directo).
 * Tabla: APROBACION_ELEMENTO.
 * No interviene EVIDENCIA ni APROBACION_EVIDENCIA.
 */
class ElementApprovalService
{
    public function __construct() {}

    // -----------------------------------------------------------------------
    // Consultas
    // -----------------------------------------------------------------------

    /**
     * Lista aprobaciones de Elements.
     * Si el usuario es Profesor, muestra todas las de su proceso
     * (filtro por ELEMENTO_ASIGNACION pendiente HU-009).
     */
    public function listApprovals()
    {
        $query = ElementApproval::with(['elemento', 'process', 'user']);

        /** @var \App\Models\User $user */
        $user = Auth::user();

        if ($user && $user->hasRole('Profesor')) {
            // TODO HU-009: filtrar solo Elements donde el profesor tiene asignación:
            // $query->whereHas('elemento.asignaciones', fn($q) => $q->where('usuario_id', $user->usuario_id));
        }

        return $query->get();
    }

    /**
     * Obtiene una aprobación específica por ID.
     */
    public function getApproval(int $aprobacionId): ?ElementApproval
    {
        return ElementApproval::with(['elemento', 'process', 'user'])->find($aprobacionId);
    }

    /**
     * Obtiene la aprobación de un elemento en un proceso específico.
     */
    public function getApprovalByElementAndProcess(int $elementoId, int $procesoId): ?ElementApproval
    {
        return ElementApproval::where('elemento_id', $elementoId)
            ->where('proceso_id', $procesoId)
            ->first();
    }

    // -----------------------------------------------------------------------
    // Aprobar / rechazar — con cascada hacia hijos
    // -----------------------------------------------------------------------

    /**
     * Recolecta recursivamente los IDs de un elemento y todos sus descendientes activos.
     * Ejemplo: pauta → [pauta_id, fuente1_id, fuente2_id]
     */
    private function collectDescendantIds(int $elementoId): array
    {
        $ids = [$elementoId];
        $children = StructureElement::where('padre_id', $elementoId)
            ->where('activo', true)
            ->pluck('elemento_id');

        foreach ($children as $childId) {
            $ids = array_merge($ids, $this->collectDescendantIds($childId));
        }

        return $ids;
    }

    /**
     * Aprueba un elemento y en cascada todos sus descendientes activos.
     *
     * - Si pasas una FUENTE (hoja): aprueba solo esa fuente.
     * - Si pasas una PAUTA:        aprueba la pauta + todas sus fuentes.
     * - Si pasas una DIMENSIÓN:    aprueba la dimensión + pautas + fuentes.
     *
     * Devuelve la aprobación del elemento raíz solicitado.
     */
    public function approveElemento(
        int $elementoId,
        int $procesoId,
        ?string $comentario = null
    ): ElementApproval {
        $elemento = StructureElement::find($elementoId);
        if (!$elemento) {
            throw new \InvalidArgumentException('El elemento especificado no existe.');
        }

        $existing = $this->getApprovalByElementAndProcess($elementoId, $procesoId);
        if ($existing && $existing->estado === 'aprobado') {
            throw new \InvalidArgumentException('El elemento ya está aprobado.');
        }

        $usuarioId  = Auth::id();
        $allIds     = $this->collectDescendantIds($elementoId);
        $totalNodos = count($allIds);

        $rootApproval = DB::transaction(function () use ($allIds, $elementoId, $procesoId, $usuarioId, $comentario) {
            $root = null;
            foreach ($allIds as $id) {
                $approval = ElementApproval::updateOrCreate(
                    ['elemento_id' => $id, 'proceso_id' => $procesoId],
                    ['usuario_id' => $usuarioId, 'estado' => 'aprobado', 'comentario' => $comentario]
                );
                if ($id === $elementoId) {
                    $root = $approval;
                }
            }
            return $root->load(['elemento', 'process', 'user']);
        });

        event(new ElementApproved($rootApproval));
        $desc = $totalNodos > 1 ? " (+ {$totalNodos} nodos en cascada)" : '';
        AuditLogService::log(
            'aprobar',
            "Elemento aprobado: {$elemento->nomenclatura} (ID: {$elementoId}){$desc}",
            'Aprobación Elements'
        );

        return $rootApproval;
    }

    /**
     * Rechaza un elemento y en cascada todos sus descendientes activos.
     *
     * - Si pasas una FUENTE (hoja): rechaza solo esa fuente.
     * - Si pasas una PAUTA:        rechaza la pauta + todas sus fuentes.
     * - Si pasas una DIMENSIÓN:    rechaza la dimensión + pautas + fuentes.
     *
     * Devuelve el rechazo del elemento raíz solicitado.
     * TODO HU-009: también actualizará ELEMENTO_ASIGNACION a estado Rechazado.
     */
    public function rejectElemento(
        int $elementoId,
        int $procesoId,
        ?string $comentario = null,
        ?string $fechaLimite = null
    ): ElementApproval {
        $elemento = StructureElement::find($elementoId);
        if (!$elemento) {
            throw new \InvalidArgumentException('El elemento especificado no existe.');
        }

        $existing = $this->getApprovalByElementAndProcess($elementoId, $procesoId);
        if ($existing && $existing->estado === 'rechazado') {
            throw new \InvalidArgumentException('El elemento ya está rechazado.');
        }

        $usuarioId  = Auth::id();
        $allIds     = $this->collectDescendantIds($elementoId);
        $totalNodos = count($allIds);

        $rootApproval = DB::transaction(function () use ($allIds, $elementoId, $procesoId, $usuarioId, $comentario, $fechaLimite) {
            $root = null;
            foreach ($allIds as $id) {
                $approval = ElementApproval::updateOrCreate(
                    ['elemento_id' => $id, 'proceso_id' => $procesoId],
                    ['usuario_id' => $usuarioId, 'estado' => 'rechazado', 'comentario' => $comentario]
                );
                if ($id === $elementoId) {
                    $root = $approval;
                }
            }

            // TODO HU-009: cuando exista ElementAssignment, actualizar todas las asignaciones:
            // $data = ['estado' => 'Rechazado'];
            // if ($comentario)  $data['comentario']   = $comentario;
            // if ($fechaLimite) $data['fecha_limite']  = $fechaLimite;
            // ElementAssignment::whereIn('elemento_id', $allIds)
            //     ->where('proceso_id', $procesoId)
            //     ->update($data);

            return $root->load(['elemento', 'process', 'user']);
        });

        event(new ElementRejected($rootApproval));
        $desc = $totalNodos > 1 ? " (+ {$totalNodos} nodos en cascada)" : '';
        AuditLogService::log(
            'rechazar',
            "Elemento rechazado: {$elemento->nomenclatura} (ID: {$elementoId}){$desc}",
            'Aprobación Elements'
        );

        return $rootApproval;
    }
}
