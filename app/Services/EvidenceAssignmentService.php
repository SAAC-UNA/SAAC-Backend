<?php

namespace App\Services;

use App\Models\EvidenceAssignment;
use App\Models\User;
use App\Models\Role;
use App\Events\EvidenceAssigned;
use Illuminate\Support\Facades\DB;

class EvidenceAssignmentService
{
    /**
     * Obtener todas las asignaciones de evidencias.
     */
    public function getAll()
    {
        $rows = DB::select('CALL SP_OBTENER_ASIGNACIONES_EVIDENCIA(?, ?, ?)', [null, null, null]);
        return EvidenceAssignment::hydrate(array_map(fn($r) => (array) $r, $rows));
    }

    /**
     * Encontrar asignacion por ID.
     */
    public function findById(int $id): ?EvidenceAssignment
    {
        $rows = DB::select('CALL SP_BUSCAR_ASIGNACION_EVIDENCIA(?)', [$id]);
        return $rows ? EvidenceAssignment::hydrate(array_map(fn($r) => (array) $r, $rows))->first() : null;
    }

    /**
     * Asignar evidencia a usuarios y/o roles.
     */
    public function assignEvidence(array $data): array
    {
        $procesoId   = $data['proceso_id'];
        $evidenciaId = $data['evidencia_id'];
        $usuarios    = $data['usuarios'] ?? [];
        $roles       = $data['roles'] ?? [];
        $fechaLimite = $data['fecha_limite'] ?? null;
        $comentario  = $data['comentario'] ?? null;

        $asignaciones = [];
        $errores      = [];

        DB::beginTransaction();
        try {
            // Verificar que el proceso existe
            $proceso = DB::select('CALL SP_BUSCAR_PROCESO(?)', [$procesoId]);
            if (empty($proceso)) {
                throw new \Exception('El proceso especificado no existe.');
            }

            // Verificar que la evidencia existe
            $evidencia = DB::select('CALL SP_BUSCAR_EVIDENCIA(?)', [$evidenciaId]);
            if (empty($evidencia)) {
                throw new \Exception('La evidencia especificada no existe.');
            }

            // Asignar a usuarios directamente
            foreach ($usuarios as $usuarioId) {
                $asignacion = $this->createAssignment($procesoId, $evidenciaId, $usuarioId, $fechaLimite, $comentario);
                if ($asignacion) {
                    $asignaciones[] = $asignacion;
                } else {
                    $errores[] = "Usuario {$usuarioId} ya tiene esta evidencia asignada en este proceso.";
                }
            }

            // Asignar a usuarios que tienen los roles especificados
            foreach ($roles as $roleId) {
                $role = Role::find($roleId);
                if (!$role) {
                    $errores[] = "El rol {$roleId} no existe.";
                    continue;
                }

                // Spatie se mantiene para gestion de roles
                $usuariosConRol = User::role($role->name)->active()->get();

                foreach ($usuariosConRol as $usuario) {
                    $asignacion = $this->createAssignment($procesoId, $evidenciaId, $usuario->usuario_id, $fechaLimite, $comentario);
                    if ($asignacion) {
                        $asignaciones[] = $asignacion;
                    } else {
                        $errores[] = "Usuario {$usuario->nombre} (rol: {$role->name}) ya tiene esta evidencia asignada en este proceso.";
                    }
                }
            }

            DB::commit();

            return [
                'asignaciones'      => $asignaciones,
                'errores'           => $errores,
                'total_asignaciones'=> count($asignaciones),
                'total_errores'     => count($errores),
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Crear una asignacion individual. Retorna null si ya existe duplicado activo.
     */
    private function createAssignment(
        int $procesoId,
        int $evidenciaId,
        int $usuarioId,
        ?string $fechaLimite,
        ?string $comentario
    ): ?EvidenceAssignment {
        // Verificar duplicado via SP
        $duplicado = DB::select('CALL SP_VERIFICAR_DUPLICADO_ASIGNACION(?, ?, ?)', [
            $procesoId, $evidenciaId, $usuarioId,
        ]);

        if (!empty($duplicado) && $duplicado[0]->total > 0) {
            return null;
        }

        $rows = DB::select('CALL SP_CREAR_ASIGNACION_EVIDENCIA(?, ?, ?, ?, ?, ?, ?)', [
            $procesoId,
            $evidenciaId,
            $usuarioId,
            'pendiente',
            now()->format('Y-m-d H:i:s'),
            $fechaLimite,
            $comentario,
        ]);

        $assignment = EvidenceAssignment::hydrate(array_map(fn($r) => (array) $r, $rows))->first();

        // Disparar evento de asignacion para notificaciones
        event(new EvidenceAssigned($assignment));

        return $assignment;
    }

    /**
     * Actualizar el estado de una asignacion.
     */
    public function updateAssignment(EvidenceAssignment $assignment, array $data): EvidenceAssignment
    {
        DB::statement('CALL SP_ACTUALIZAR_ASIGNACION_EVIDENCIA(?, ?, ?, ?)', [
            $assignment->evidencia_asignacion_id,
            $data['estado'] ?? null,
            $data['fecha_limite'] ?? null,
            $data['comentario'] ?? null,
        ]);

        $rows = DB::select('CALL SP_BUSCAR_ASIGNACION_EVIDENCIA(?)', [$assignment->evidencia_asignacion_id]);
        return EvidenceAssignment::hydrate(array_map(fn($r) => (array) $r, $rows))->first();
    }

    /**
     * Eliminar una asignacion.
     */
    public function deleteAssignment(EvidenceAssignment $assignment): void
    {
        DB::statement('CALL SP_ELIMINAR_ASIGNACION_EVIDENCIA(?)', [$assignment->evidencia_asignacion_id]);
    }

    /**
     * Obtener asignaciones por usuario.
     */
    public function getAssignmentsByUser(int $usuarioId)
    {
        $rows  = DB::select('CALL SP_OBTENER_ASIGNACIONES_EVIDENCIA(?, ?, ?)', [null, $usuarioId, null]);
        $items = EvidenceAssignment::hydrate(array_map(fn($r) => (array) $r, $rows));
        // Cargar el criterio a través de la evidencia para que EvidenceAssignmentResource
        // pueda exponer criterion en lugar de retornar null (el SP no lo incluye como campo plano).
        $items->loadMissing('evidence.criterion');
        return $items;
    }

    /**
     * Obtener asignaciones por evidencia.
     */
    public function getAssignmentsByEvidence(int $evidenciaId)
    {
        $rows = DB::select('CALL SP_OBTENER_ASIGNACIONES_EVIDENCIA(?, ?, ?)', [null, null, $evidenciaId]);
        return EvidenceAssignment::hydrate(array_map(fn($r) => (array) $r, $rows));
    }

    /**
     * Obtener asignaciones por proceso.
     */
    public function getAssignmentsByProcess(int $procesoId)
    {
        $rows = DB::select('CALL SP_OBTENER_ASIGNACIONES_EVIDENCIA(?, ?, ?)', [$procesoId, null, null]);
        return EvidenceAssignment::hydrate(array_map(fn($r) => (array) $r, $rows));
    }

    /**
     * Obtener evidencias proximas a vencer para un usuario.
     */
    public function getUpcomingEvidences(int $usuarioId, string $limitDate)
    {
        $rows = DB::select('CALL SP_OBTENER_EVIDENCIAS_PROXIMAS(?, ?)', [$usuarioId, $limitDate]);
        return EvidenceAssignment::hydrate(array_map(fn($r) => (array) $r, $rows));
    }
}