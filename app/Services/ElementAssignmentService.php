<?php

namespace App\Services;

use App\Models\ElementAssignment;
use App\Models\ExtensionRequest;
use App\Models\StructureElement;
use App\Models\StructureModel;
use App\Models\Process;
use App\Models\User;
use App\Models\Comment;
use App\Models\Notification;
use App\Models\Role;
use App\Events\ElementAssigned;
use App\Services\AuditLogService;
use App\Services\NotificationService;
use Illuminate\Support\Facades\DB;

class ElementAssignmentService
{
    private const WITH_BASE = ['element', 'user', 'process', 'assignedBy'];

    private function baseQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return ElementAssignment::with(self::WITH_BASE);
    }

    /**
     * Get all element assignments.
     */
    public function getAll()
    {
        return $this->baseQuery()
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Filter element assignments for the flexible model explorer.
     * Used by GET /api/elementos-asignaciones/filtrar
     */
    public function filter(array $filters, User $user): array
    {
        $query = ElementAssignment::with(['element', 'user', 'process']);

        if (!empty($filters['proceso_id'])) {
            $query->where('proceso_id', (int) $filters['proceso_id']);
        }

        if (!empty($filters['elemento_id'])) {
            $query->where('elemento_id', (int) $filters['elemento_id']);
        }

        if (!empty($filters['estado'])) {
            $query->where('estado', $filters['estado']);
        }

        if (!empty($filters['usuario_id'])) {
            $query->where('usuario_id', (int) $filters['usuario_id']);
        }

        // Usuarios sin rol de gestión solo ven sus propias asignaciones
        if (!$user->hasRole(['Superusuario', 'Administrador', 'Encargado de Acreditación'])) {
            $query->where('usuario_id', $user->usuario_id);
        }

        $perPage = (int) ($filters['per_page'] ?? 15);
        $page    = (int) ($filters['page'] ?? 1);

        $paginated = $query->orderBy('created_at', 'desc')->paginate($perPage, ['*'], 'page', $page);

        return [
            'data' => $paginated->items(),
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page'    => $paginated->lastPage(),
                'per_page'     => $paginated->perPage(),
                'total'        => $paginated->total(),
            ],
        ];
    }

    /**
     * Find an assignment by ID.
     */
    public function findById(int $id): ?ElementAssignment
    {
        return $this->baseQuery()->find($id);
    }

    /**
     * Assign an element to users and/or roles.
     * HU-007 (flexible model)
     */
    public function assignElement(array $data): array
    {
        $processId   = $data['proceso_id'];
        $elementId   = $data['elemento_id'];
        $users       = $data['usuarios'] ?? [];
        $roles       = $data['roles'] ?? [];
        $deadline    = $data['fecha_limite'] ?? null;
        $comment     = $data['comentario'] ?? null;
        $assignedBy  = $data['asignado_por'] ?? null;

        $assignments = [];
        $errors      = [];

        DB::beginTransaction();
        try {
            $process = Process::find($processId);
            if (!$process) {
                throw new \Exception('The specified process does not exist.');
            }

            // Guard Arquitectura B: solo se puede asignar elementos en ciclos de modelo flexible
            $tipoModelo = optional(
                optional($process->accreditationCycle)->modeloEstructura
            )->tipo;

            if ($tipoModelo === StructureModel::TIPO_TRADICIONAL) {
                throw new \InvalidArgumentException(
                    'El proceso pertenece a un ciclo con modelo tradicional. ' .
                    'Las asignaciones de elemento solo aplican al modelo flexible.'
                );
            }

            $element = StructureElement::find($elementId);
            if (!$element) {
                throw new \Exception('The specified element does not exist.');
            }

            // Guard Estrategia 3: el modelo define qué tipos de nodo pueden recibir asignaciones
            $tiposAsignables = optional($element->modeloEstructura)->tipos_asignables;
            if (!empty($tiposAsignables) && !in_array($element->tipo, $tiposAsignables)) {
                throw new \InvalidArgumentException(
                    "El elemento de tipo '{$element->tipo}' no acepta asignaciones en este modelo. " .
                    'Tipos permitidos: ' . implode(', ', $tiposAsignables) . '.'
                );
            }

            foreach ($users as $userId) {
                $assignment = $this->createAssignment(
                    $processId, $elementId, $userId,
                    $assignedBy, $deadline, $comment
                );

                if ($assignment) {
                    $assignments[] = $assignment;
                } else {
                    $errors[] = "User {$userId} is already assigned to this element in this process.";
                }
            }

            foreach ($roles as $roleId) {
                $role = Role::find($roleId);
                if (!$role) {
                    $errors[] = "Role {$roleId} does not exist.";
                    continue;
                }

                $usersWithRole = User::role($role->name)->active()->get();

                foreach ($usersWithRole as $user) {
                    $assignment = $this->createAssignment(
                        $processId, $elementId, $user->usuario_id,
                        $assignedBy, $deadline, $comment
                    );

                    if ($assignment) {
                        $assignments[] = $assignment;
                    } else {
                        $errors[] = "User {$user->nombre} (role: {$role->name}) is already assigned to this element.";
                    }
                }
            }

            DB::commit();

            return [
                'assignments'       => $assignments,
                'errors'            => $errors,
                'total_assignments' => count($assignments),
                'total_errors'      => count($errors),
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Create a single assignment. Returns null if a duplicate already exists.
     */
    private function createAssignment(
        int $processId,
        int $elementId,
        int $userId,
        ?int $assignedBy,
        ?string $deadline,
        ?string $comment
    ): ?ElementAssignment {
        $exists = ElementAssignment::where('proceso_id', $processId)
            ->where('elemento_id', $elementId)
            ->where('usuario_id', $userId)
            ->exists();

        if ($exists) {
            return null;
        }

        $assignment = ElementAssignment::create([
            'proceso_id'  => $processId,
            'elemento_id' => $elementId,
            'usuario_id'  => $userId,
            'asignado_por'=> $assignedBy,
            'estado'      => 'Pendiente',
            'fecha_limite'=> $deadline,
            'comentario'  => $comment,
        ]);

        $assignment->load(self::WITH_BASE);

        // Fire event for notifications (HU-018)
        event(new ElementAssigned($assignment));

        return $assignment;
    }

    /**
     * Update an assignment (state, deadline, comment).
     */
    public function updateAssignment(ElementAssignment $assignment, array $data): ElementAssignment
    {
        $assignment->update(array_filter([
            'estado'       => $data['estado']       ?? null,
            'fecha_limite' => $data['fecha_limite']  ?? null,
            'comentario'   => $data['comentario']    ?? null,
        ], fn ($v) => $v !== null));

        return $this->baseQuery()->find($assignment->getKey());
    }

    /**
     * Delete an assignment.
     */
    public function deleteAssignment(ElementAssignment $assignment): void
    {
        $assignment->delete();
    }

    /**
     * Get assignments by user.
     */
    public function getByUser(int $userId)
    {
        return $this->baseQuery()
            ->where('usuario_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get assignments by element.
     */
    public function getByElement(int $elementId)
    {
        return $this->baseQuery()
            ->where('elemento_id', $elementId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get assignments by process.
     */
    public function getByProcess(int $processId)
    {
        return $this->baseQuery()
            ->where('proceso_id', $processId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Retroalimentar una asignación de elemento (HU-013 equivalente flexible).
     *
     * Cambia el estado a 'Observada' o 'Validada', guarda un comentario polimórfico
     * y notifica al usuario asignado.
     */
    public function retroalimentar(ElementAssignment $assignment, array $data, User $reviewer): ElementAssignment
    {
        if ($assignment->estado === ElementAssignment::ESTADO_PENDIENTE) {
            throw new \InvalidArgumentException(
                "No se puede retroalimentar una asignación en estado \"{$assignment->estado}\". "
                . "Debe estar en progreso, completada, observada, validada o vencida."
            );
        }

        $updated = DB::transaction(function () use ($assignment, $data, $reviewer) {
            $assignment->update(['estado' => $data['estado']]);

            Comment::create([
                'usuario_id'       => $reviewer->usuario_id,
                'commentable_type' => ElementAssignment::class,
                'commentable_id'   => $assignment->elemento_asignacion_id,
                'texto'            => $data['comentario'],
            ]);

            AuditLogService::log(
                'retroalimentar',
                "Asignación de elemento ID {$assignment->elemento_asignacion_id} marcada como \"{$data['estado']}\". Comentario: {$data['comentario']}",
                'ElementAsignacion',
                $reviewer->usuario_id
            );

            return $assignment->fresh([...self::WITH_BASE, 'comments.user']);
        });

        // Notificación fuera de la transacción — un fallo de email no revierte el estado
        try {
            $user = $updated->user;
            if ($user) {
                NotificationService::create([
                    'usuario_id'   => $user->usuario_id,
                    'tipo_evento'  => $data['estado'] === ElementAssignment::ESTADO_OBSERVADA
                        ? Notification::TIPO_DEVOLUCION_OBSERVACION
                        : Notification::TIPO_APROBACION_EVIDENCIA,
                    'titulo'       => 'Elemento asignado — ' . strtoupper($data['estado']),
                    'mensaje'      => "El evaluador {$reviewer->nombre} marcó la asignación como \"{$data['estado']}\". Comentario: {$data['comentario']}",
                    'relacionado'  => $updated->element,
                    'enlace'       => "/Elements/{$updated->elemento_id}",
                    'forzar_email' => true,
                ]);
            }
        } catch (\Throwable $e) {
            logger()->error('ElementAsignacionRetroalimentada notification failed', [
                'elemento_asignacion_id' => $assignment->elemento_asignacion_id,
                'error'                  => $e->getMessage(),
            ]);
        }

        return $updated;
    }
}
