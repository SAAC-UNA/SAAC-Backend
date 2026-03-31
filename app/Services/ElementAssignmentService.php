<?php

namespace App\Services;

use App\Models\ElementAssignment;
use App\Models\StructureElement;
use App\Models\Process;
use App\Models\User;
use App\Models\Role;
use App\Events\ElementAssigned;
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
}
