<?php

namespace App\Services;

use App\Models\EvidenceAssignment;
use App\Models\Evidence;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class EvidenceAssignmentService
{
    /**
     * Obtener todas las asignaciones de evidencias.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAll()
    {
        return EvidenceAssignment::with(['process', 'evidence', 'user'])
            ->orderBy('fecha_asignacion', 'desc')
            ->get();
    }

    /**
     * Encontrar asignación por ID.
     *
     * @param int $id
     * @return EvidenceAssignment|null
     */
    public function findById(int $id): ?EvidenceAssignment
    {
        return EvidenceAssignment::with(['process', 'evidence', 'user'])->find($id);
    }

    /**
     * Asignar evidencia a usuarios y/o roles.
     *
     * @param array $data
     * @return array
     */
    public function assignEvidence(array $data): array
    {
        $procesoId = $data['proceso_id'];
        $evidenciaId = $data['evidencia_id'];
        $usuarios = $data['usuarios'] ?? [];
        $roles = $data['roles'] ?? [];
        $fechaLimite = $data['fecha_limite'] ?? null;
        $comentario = $data['comentario'] ?? null;

        $asignaciones = [];
        $errores = [];

        DB::beginTransaction();
        try {
            // Verificar que el proceso existe
            $proceso = \App\Models\Process::find($procesoId);
            if (!$proceso) {
                throw new \Exception('El proceso especificado no existe.');
            }

            // Verificar que la evidencia existe
            $evidencia = Evidence::find($evidenciaId);
            if (!$evidencia) {
                throw new \Exception('La evidencia especificada no existe.');
            }

            // Asignar a usuarios directamente
            foreach ($usuarios as $usuarioId) {
                $asignacion = $this->createAssignment(
                    $procesoId,
                    $evidenciaId,
                    $usuarioId,
                    $fechaLimite,
                    $comentario
                );
                
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

                // Obtener usuarios activos que tienen este rol usando Spatie
                $usuariosConRol = User::role($role->name)->active()->get();
                
                foreach ($usuariosConRol as $usuario) {
                    $asignacion = $this->createAssignment(
                        $procesoId,
                        $evidenciaId,
                        $usuario->usuario_id,
                        $fechaLimite,
                        $comentario
                    );
                    
                    if ($asignacion) {
                        $asignaciones[] = $asignacion;
                    } else {
                        $errores[] = "Usuario {$usuario->nombre} (rol: {$role->name}) ya tiene esta evidencia asignada en este proceso.";
                    }
                }
            }

            DB::commit();

            return [
                'asignaciones' => $asignaciones,
                'errores' => $errores,
                'total_asignaciones' => count($asignaciones),
                'total_errores' => count($errores)
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Crear una asignación individual.
     *
     * @param int $procesoId
     * @param int $evidenciaId
     * @param int $usuarioId
     * @param string|null $fechaLimite
     * @param string|null $comentario
     * @return EvidenceAssignment|null
     */
    private function createAssignment(int $procesoId, int $evidenciaId, int $usuarioId, ?string $fechaLimite, ?string $comentario): ?EvidenceAssignment
    {
        // Verificar que no exista ya una asignación activa en este proceso
        $existente = EvidenceAssignment::where('proceso_id', $procesoId)
            ->where('evidencia_id', $evidenciaId)
            ->where('usuario_id', $usuarioId)
            ->where('estado', '!=', 'completado')
            ->first();

        if ($existente) {
            return null; // Ya existe una asignación activa en este proceso
        }

        return EvidenceAssignment::create([
            'proceso_id' => $procesoId,
            'evidencia_id' => $evidenciaId,
            'usuario_id' => $usuarioId,
            'estado' => 'pendiente',
            'fecha_asignacion' => now(),
            'fecha_limite' => $fechaLimite,
        ]);
    }

    /**
     * Actualizar el estado de una asignación.
     *
     * @param EvidenceAssignment $assignment
     * @param array $data
     * @return EvidenceAssignment
     */
    public function updateAssignment(EvidenceAssignment $assignment, array $data): EvidenceAssignment
    {
        $assignment->fill($data)->save();
        return $assignment;
    }

    /**
     * Eliminar una asignación.
     *
     * @param EvidenceAssignment $assignment
     * @return void
     */
    public function deleteAssignment(EvidenceAssignment $assignment): void
    {
        $assignment->delete();
    }

    /**
     * Obtener asignaciones por usuario.
     *
     * @param int $usuarioId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAssignmentsByUser(int $usuarioId)
    {
        return EvidenceAssignment::with(['evidence', 'evidence.criterion'])
            ->where('usuario_id', $usuarioId)
            ->orderBy('fecha_limite', 'asc')
            ->get();
    }

    /**
     * Obtener asignaciones por evidencia.
     *
     * @param int $evidenciaId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAssignmentsByEvidence(int $evidenciaId)
    {
        return EvidenceAssignment::with(['user'])
            ->where('evidencia_id', $evidenciaId)
            ->orderBy('fecha_asignacion', 'desc')
            ->get();
    }

    /**
     * Obtener asignaciones por proceso.
     *
     * @param int $procesoId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAssignmentsByProcess(int $procesoId)
    {
        return EvidenceAssignment::with(['evidence', 'user'])
            ->where('proceso_id', $procesoId)
            ->orderBy('fecha_asignacion', 'desc')
            ->get();
    }
}
