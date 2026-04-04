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
    public function listApprovals(?string $estado = null)
    {
        $query = ElementApproval::with(['elemento', 'process', 'user']);

        /** @var \App\Models\User $user */
        $user = Auth::user();

        if ($user && $user->hasRole('Profesor')) {
            $query->whereHas(
                'elemento.assignments',
                fn($q) => $q->where('usuario_id', $user->usuario_id)
            );
        }

        if ($estado !== null) {
            $query->where('estado', $estado);
        }

        return $query->get();
    }

    /**
     * Obtiene una aprobación específica por ID.
     */
    public function getApproval(int $aprobacionId): ?ElementApproval
    {
        $approval = ElementApproval::with(['elemento', 'process', 'user'])->find($aprobacionId);

        if ($approval) {
            $childIds = $approval->elemento->children->pluck('elemento_id');
            $approval->hijos = ElementApproval::with(['elemento'])
                ->whereIn('elemento_id', $childIds)
                ->where('proceso_id', $approval->proceso_id)
                ->get();
        }

        return $approval;
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
    ): array {
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

        $result = DB::transaction(function () use ($allIds, $elementoId, $procesoId, $usuarioId, $comentario) {
            $root     = null;
            $cascada  = [];
            foreach ($allIds as $id) {
                $approval = ElementApproval::updateOrCreate(
                    ['elemento_id' => $id, 'proceso_id' => $procesoId],
                    ['usuario_id' => $usuarioId, 'estado' => 'aprobado', 'comentario' => $comentario]
                )->load(['elemento', 'process', 'user']);

                if ($id === $elementoId) {
                    $root = $approval;
                } else {
                    $cascada[] = $approval;
                }
            }
            return ['raiz' => $root, 'cascada' => $cascada];
        });

        event(new ElementApproved($result['raiz']));
        $total = count($allIds);
        $desc  = $total > 1 ? " (+ {$total} nodos en cascada)" : '';
        AuditLogService::log(
            'aprobar',
            "Elemento aprobado: {$elemento->nomenclatura} (ID: {$elementoId}){$desc}",
            'Aprobación Elements'
        );

        return $result;
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
    ): array {
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

        $result = DB::transaction(function () use ($allIds, $elementoId, $procesoId, $usuarioId, $comentario, $fechaLimite) {
            $root    = null;
            $cascada = [];
            foreach ($allIds as $id) {
                $approval = ElementApproval::updateOrCreate(
                    ['elemento_id' => $id, 'proceso_id' => $procesoId],
                    ['usuario_id' => $usuarioId, 'estado' => 'rechazado', 'comentario' => $comentario]
                )->load(['elemento', 'process', 'user']);

                if ($id === $elementoId) {
                    $root = $approval;
                } else {
                    $cascada[] = $approval;
                }
            }

            // TODO HU-009: actualizar ELEMENTO_ASIGNACION a estado Rechazado:
            // ElementAssignment::whereIn('elemento_id', $allIds)
            //     ->where('proceso_id', $procesoId)
            //     ->update(['estado' => 'Rechazado', 'comentario' => $comentario, 'fecha_limite' => $fechaLimite]);

            return ['raiz' => $root, 'cascada' => $cascada];
        });

        event(new ElementRejected($result['raiz']));
        $total = count($allIds);
        $desc  = $total > 1 ? " (+ {$total} nodos en cascada)" : '';
        AuditLogService::log(
            'rechazar',
            "Elemento rechazado: {$elemento->nomenclatura} (ID: {$elementoId}){$desc}",
            'Aprobación Elements'
        );

        return $result;
    }
}
