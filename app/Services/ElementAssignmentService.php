<?php

namespace App\Services;

use App\Models\ElementAssignment;
use App\Models\ExtensionRequest;
use App\Models\StructureElement;
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

            $element = StructureElement::find($elementId);
            if (!$element) {
                throw new \Exception('The specified element does not exist.');
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
                'ElementoAsignacion',
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
                    'enlace'       => "/elementos/{$updated->elemento_id}",
                    'forzar_email' => true,
                ]);
            }
        } catch (\Throwable $e) {
            logger()->error('ElementoAsignacionRetroalimentada notification failed', [
                'elemento_asignacion_id' => $assignment->elemento_asignacion_id,
                'error'                  => $e->getMessage(),
            ]);
        }

        return $updated;
    }

    /**
     * Solicitar ampliación de plazo para una asignación de elemento (HU-016 equivalente flexible).
     */
    public function solicitarAmpliacion(ElementAssignment $assignment, array $data, int $userId): ExtensionRequest
    {
        return DB::transaction(function () use ($assignment, $data, $userId) {
            if ($assignment->usuario_id !== $userId) {
                throw new \InvalidArgumentException(
                    'Solo puede solicitar ampliación para asignaciones asignadas a usted.'
                );
            }

            $tienePendiente = ExtensionRequest::where('elemento_asignacion_id', $assignment->elemento_asignacion_id)
                ->where('estado', ExtensionRequest::ESTADO_PENDIENTE)
                ->exists();

            if ($tienePendiente) {
                throw new \InvalidArgumentException(
                    'Ya existe una solicitud de ampliación pendiente para esta asignación.'
                );
            }

            if ($assignment->fecha_limite) {
                $fechaLimite = \Carbon\Carbon::parse($assignment->fecha_limite);
                $sugerida    = \Carbon\Carbon::parse($data['fecha_sugerida']);

                if ($sugerida->lte($fechaLimite)) {
                    throw new \InvalidArgumentException(
                        'La fecha sugerida debe ser posterior a la fecha límite actual ('
                        . $fechaLimite->format('d/m/Y') . ').'
                    );
                }

                if ($fechaLimite->diffInDays($sugerida) > 30) {
                    throw new \InvalidArgumentException(
                        'La ampliación no puede exceder 30 días desde la fecha límite actual.'
                    );
                }
            }

            return ExtensionRequest::create([
                'elemento_asignacion_id' => $assignment->elemento_asignacion_id,
                'evidencia_asignacion_id'=> null,
                'usuario_id'             => $userId,
                'motivo'                 => $data['motivo'],
                'fecha_sugerida'         => $data['fecha_sugerida'],
                'estado'                 => ExtensionRequest::ESTADO_PENDIENTE,
            ]);
        });
    }
}
