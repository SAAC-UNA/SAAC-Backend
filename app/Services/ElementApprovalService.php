<?php

namespace App\Services;

use App\Models\ElementApproval;
use App\Models\ElementAssignment;
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
     * Recalcula y persiste el estado del bloque padre según las decisiones
     * individuales de sus hijos directos activos.
     *
     * Reglas (equivalente a recalculateBlockState en CriterionApprovalService):
     *   - Todos aprobados                         → 'aprobado'
     *   - Todos rechazados                        → 'rechazado'
     *   - Al menos 1 rechazado (no todos iguales) → 'incompleto'
     *   - Ninguna decisión tomada aún             → 'pendiente'
     */
    public function recalculateParentState(int $padreId, int $procesoId): void
    {
        $block = ElementApproval::where('elemento_id', $padreId)
            ->where('proceso_id', $procesoId)
            ->first();

        if (!$block) {
            return;
        }

        $childIds = StructureElement::where('padre_id', $padreId)
            ->where('activo', true)
            ->pluck('elemento_id');

        $total    = $childIds->count();
        $approved = ElementApproval::whereIn('elemento_id', $childIds)
            ->where('proceso_id', $procesoId)
            ->where('estado', 'aprobado')
            ->count();
        $rejected = ElementApproval::whereIn('elemento_id', $childIds)
            ->where('proceso_id', $procesoId)
            ->where('estado', 'rechazado')
            ->count();

        if ($total > 0 && $approved === $total) {
            $block->estado = 'aprobado';
        } elseif ($total > 0 && $rejected === $total) {
            $block->estado = 'rechazado';
        } elseif ($rejected >= 1) {
            $block->estado = 'incompleto';
        } else {
            $block->estado = 'pendiente';
        }

        $block->save();
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
                    ['usuario_id' => $usuarioId, 'estado' => 'rechazado', 'comentario' => $comentario, 'nueva_fecha_limite' => $fechaLimite]
                )->load(['elemento', 'process', 'user']);

                if ($id === $elementoId) {
                    $root = $approval;
                } else {
                    $cascada[] = $approval;
                }
            }

            // Resetear asignaciones a Pendiente para que los responsables reenvíen
            $assignmentUpdate = ['estado' => ElementAssignment::ESTADO_PENDIENTE];
            if ($fechaLimite !== null) {
                $assignmentUpdate['fecha_limite'] = $fechaLimite;
            }
            ElementAssignment::whereIn('elemento_id', $allIds)
                ->where('proceso_id', $procesoId)
                ->update($assignmentUpdate);

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

    // -----------------------------------------------------------------------
    // Aprobación / rechazo individual de hijo (fuente dentro de pauta)
    // -----------------------------------------------------------------------

    /**
     * Aprueba un elemento hijo (fuente) individualmente dentro del bloque padre.
     *
     * Regla de bloqueo: si el bloque padre está 'incompleto', un hijo ya
     * 'aprobado' no puede volver a tocarse.
     *
     * Tras guardar, recalcula el estado del bloque padre automáticamente.
     *
     * @throws \InvalidArgumentException Si el hijo no pertenece al padre o está inactivo.
     * @throws \LogicException           Si el hijo ya está aprobado y el bloque es 'incompleto'.
     */
    public function approveIndividualChild(
        int $padreId,
        int $hijoId,
        int $procesoId
    ): array {
        // Validaciones fuera de la transacción para que el Handler las capture directamente
        $hijo = StructureElement::where('elemento_id', $hijoId)
            ->where('padre_id', $padreId)
            ->where('activo', true)
            ->firstOrFail();

        if (!$hijo) {
            throw new \InvalidArgumentException(
                'El elemento hijo no existe, no pertenece al padre indicado o está inactivo.'
            );
        }

        $padreApproval = ElementApproval::where('elemento_id', $padreId)
            ->where('proceso_id', $procesoId)
            ->first();

        $existing = ElementApproval::where('elemento_id', $hijoId)
            ->where('proceso_id', $procesoId)
            ->first();

        if ($existing && $existing->estado === 'aprobado' && $padreApproval && $padreApproval->estado === 'incompleto') {
            throw new \LogicException(
                'Este elemento ya fue aprobado y está bloqueado mientras el bloque tenga estado incompleto.'
            );
        }

        return DB::transaction(function () use ($padreId, $hijoId, $procesoId, $hijo) {
            $usuarioId = Auth::id();

            $parentApproval = ElementApproval::firstOrCreate(
                ['elemento_id' => $padreId, 'proceso_id' => $procesoId],
                ['usuario_id' => $usuarioId, 'estado' => 'pendiente', 'comentario' => null]
            );

            $childApproval = ElementApproval::updateOrCreate(
                ['elemento_id' => $hijoId, 'proceso_id' => $procesoId],
                [
                    'usuario_id' => $usuarioId,
                    'estado'     => 'aprobado',
                    'comentario' => null,
                ]
            );

            $this->recalculateParentState($padreId, $procesoId);

            $padre = StructureElement::find($padreId);
            AuditLogService::log(
                'aprobar',
                "Hijo '{$hijo->nomenclatura}' aprobado individualmente en bloque '{$padre->nomenclatura}'",
                'Aprobación Elements'
            );

            return [
                'padre_approval' => $parentApproval->fresh()->load(['elemento', 'process', 'user']),
                'hijo_approval'  => $childApproval->load(['elemento']),
            ];
        });
    }

    /**
     * Rechaza un elemento hijo (fuente) individualmente dentro del bloque padre.
     *
     * Regla de bloqueo: si el bloque padre está 'incompleto', un hijo ya
     * 'aprobado' no puede cambiarse a rechazado.
     *
     * Al rechazar se guarda el comentario en APROBACION_ELEMENTO del hijo
     * y se actualiza ELEMENTO_ASIGNACION con la nueva fecha límite (si se
     * proporciona), reseteando el estado de la asignación a 'Pendiente'.
     *
     * Tras guardar, recalcula el estado del bloque padre automáticamente.
     *
     * @throws \InvalidArgumentException Si el hijo no pertenece al padre o está inactivo.
     * @throws \LogicException           Si el hijo ya está aprobado y el bloque es 'incompleto'.
     */
    public function rejectIndividualChild(
        int $padreId,
        int $hijoId,
        int $procesoId,
        ?string $comentario = null,
        ?string $nuevaFechaLimite = null
    ): array {
        // Validaciones fuera de la transacción para que el Handler las capture directamente
        $hijo = StructureElement::where('elemento_id', $hijoId)
            ->where('padre_id', $padreId)
            ->where('activo', true)
            ->first();

        if (!$hijo) {
            throw new \InvalidArgumentException(
                'El elemento hijo no existe, no pertenece al padre indicado o está inactivo.'
            );
        }

        $padreApproval = ElementApproval::where('elemento_id', $padreId)
            ->where('proceso_id', $procesoId)
            ->first();

        $existing = ElementApproval::where('elemento_id', $hijoId)
            ->where('proceso_id', $procesoId)
            ->first();

        if ($existing && $existing->estado === 'aprobado' && $padreApproval && $padreApproval->estado === 'incompleto') {
            throw new \LogicException(
                'Este elemento ya fue aprobado y está bloqueado mientras el bloque tenga estado incompleto.'
            );
        }

        return DB::transaction(function () use ($padreId, $hijoId, $procesoId, $comentario, $nuevaFechaLimite, $hijo) {
            $usuarioId = Auth::id();

            $parentApproval = ElementApproval::firstOrCreate(
                ['elemento_id' => $padreId, 'proceso_id' => $procesoId],
                ['usuario_id' => $usuarioId, 'estado' => 'pendiente', 'comentario' => null]
            );

            $childApproval = ElementApproval::updateOrCreate(
                ['elemento_id' => $hijoId, 'proceso_id' => $procesoId],
                [
                    'usuario_id'         => $usuarioId,
                    'estado'             => 'rechazado',
                    'comentario'         => $comentario,
                    'nueva_fecha_limite' => $nuevaFechaLimite,
                ]
            );

            // Devolver la asignación al responsable: resetear estado + guardar observación + nueva fecha
            $assignmentUpdate = ['estado' => ElementAssignment::ESTADO_PENDIENTE];
            if ($comentario !== null) {
                $assignmentUpdate['comentario'] = $comentario;
            }
            if ($nuevaFechaLimite !== null) {
                $assignmentUpdate['fecha_limite'] = $nuevaFechaLimite;
            }

            ElementAssignment::where('elemento_id', $hijoId)
                ->where('proceso_id', $procesoId)
                ->update($assignmentUpdate);

            $this->recalculateParentState($padreId, $procesoId);

            $padre = StructureElement::find($padreId);
            AuditLogService::log(
                'rechazar',
                "Hijo '{$hijo->nomenclatura}' rechazado individualmente en bloque '{$padre->nomenclatura}'",
                'Aprobación Elements'
            );

            return [
                'padre_approval' => $parentApproval->fresh()->load(['elemento', 'process', 'user']),
                'hijo_approval'  => $childApproval->load(['elemento']),
            ];
        });
    }
}
