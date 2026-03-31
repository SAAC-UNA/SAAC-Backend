<?php

namespace App\Services;

use App\Models\EvidenceAssignment;
use App\Models\Evidence;
use App\Models\Process;
use App\Models\StructureModel;
use App\Models\User;
use App\Models\Role;
use App\Models\File;
use App\Events\EvidenceAssigned;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EvidenceAssignmentService
{
    /** Relaciones que se cargan en casi todos los queries */
    private const WITH_BASE = ['evidence.criterion', 'evidence.comments.user', 'user', 'process'];

    /**
     * Builder base con eager loading de relaciones y EXISTS para solicitudes pendientes.
     * withExists evita el N+1 que producía el DB::table inline en EvidenceAssignmentResource.
     */
    private function baseQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return EvidenceAssignment::with(self::WITH_BASE)
            ->withExists([
                'pendingExtensionRequests as has_pending_extension_request',
                'filesByAssignee as has_uploaded_files',
            ]);
    }

    /**
     * Obtener todas las asignaciones de evidencias.
     */
    public function getAll()
    {
        return $this->baseQuery()
            ->orderBy('fecha_asignacion', 'desc')
            ->get();
    }

    /**
     * Encontrar asignacion por ID.
     */
    public function findById(int $id): ?EvidenceAssignment
    {
        return $this->baseQuery()->find($id);
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
            // Verificar existencia con Eloquent (evita N+1 si se reutiliza)
            $proceso = Process::with('accreditationCycle.modeloEstructura')->find($procesoId);
            if (!$proceso) {
                throw new \Exception('El proceso especificado no existe.');
            }

            $evidencia = Evidence::find($evidenciaId);
            if (!$evidencia) {
                throw new \Exception('La evidencia especificada no existe.');
            }

            // MODELO FLEXIBLE (HU-007 / Arquitectura B): el flujo flexible es
            // PROCESO → ELEMENTO → ELEMENTO_ASIGNACION → ARCHIVO(elemento_id).
            // EVIDENCIA_ASIGNACION solo existe en el modelo tradicional.
            $tipoModelo = $proceso->accreditationCycle?->modeloEstructura?->tipo;
            if ($tipoModelo === StructureModel::TIPO_ELEMENTO_FLEXIBLE) {
                throw new \InvalidArgumentException(
                    'El proceso pertenece a un ciclo con modelo flexible. '
                    . 'Use la API de asignaciones de elementos (POST /api/elementos-asignaciones) en su lugar.'
                );
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

                // Spatie se mantiene para gestión de roles
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
                'asignaciones'       => $asignaciones,
                'errores'            => $errores,
                'total_asignaciones' => count($asignaciones),
                'total_errores'      => count($errores),
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
        // Verificar duplicado con Eloquent — 1 query, sin SP extra
        $existe = EvidenceAssignment::where('proceso_id', $procesoId)
            ->where('evidencia_id', $evidenciaId)
            ->where('usuario_id', $usuarioId)
            ->exists();

        if ($existe) {
            return null;
        }

        $assignment = EvidenceAssignment::create([
            'proceso_id'       => $procesoId,
            'evidencia_id'     => $evidenciaId,
            'usuario_id'       => $usuarioId,
            'estado'           => 'Pendiente',
            'fecha_asignacion' => now(),
            'fecha_limite'     => $fechaLimite,
            'comentario'       => $comentario,
        ]);

        // Cargar relaciones para el evento y el retorno
        $assignment->load(self::WITH_BASE);
        $assignment->loadExists('pendingExtensionRequests as has_pending_extension_request');

        // Disparar evento de asignacion para notificaciones
        event(new EvidenceAssigned($assignment));

        return $assignment;
    }

    /**
     * Actualizar el estado de una asignacion.
     */
    public function updateAssignment(EvidenceAssignment $assignment, array $data): EvidenceAssignment
    {
        $apiEstado = isset($data['estado']) ? EvidenceAssignment::apiStatusFromDb($data['estado']) : null;

        if ($apiEstado === 'completado') {
            $hasUploadedFiles = File::query()
                ->where('evidencia_id', $assignment->evidencia_id)
                ->where('usuario_id', $assignment->usuario_id)
                ->where('proceso_id', $assignment->proceso_id)
                ->exists();

            if (!$hasUploadedFiles) {
                throw ValidationException::withMessages([
                    'estado' => 'Debe subir al menos un archivo o enlace antes de marcar la evidencia como completada.',
                ]);
            }
        }

        $assignment->update(array_filter([
            'estado'      => EvidenceAssignment::dbStatusFromApi($data['estado'] ?? null),
            'fecha_limite'=> $data['fecha_limite'] ?? null,
            'comentario'  => $data['comentario']  ?? null,
        ], fn ($v) => $v !== null));

        return $this->baseQuery()->find($assignment->getKey());
    }

    /**
     * Eliminar una asignacion.
     */
    public function deleteAssignment(EvidenceAssignment $assignment): void
    {
        $assignment->delete();
    }

    /**
     * Obtener asignaciones por usuario.
     */
    public function getAssignmentsByUser(int $usuarioId)
    {
        return $this->baseQuery()
            ->where('usuario_id', $usuarioId)
            ->orderBy('fecha_asignacion', 'desc')
            ->get();
    }

    /**
     * Obtener asignaciones por evidencia.
     */
    public function getAssignmentsByEvidence(int $evidenciaId)
    {
        return $this->baseQuery()
            ->where('evidencia_id', $evidenciaId)
            ->orderBy('fecha_asignacion', 'desc')
            ->get();
    }

    /**
     * Obtener asignaciones por proceso.
     */
    public function getAssignmentsByProcess(int $procesoId)
    {
        return $this->baseQuery()
            ->where('proceso_id', $procesoId)
            ->orderBy('fecha_asignacion', 'desc')
            ->get();
    }

    /**
     * Obtener evidencias próximas a vencer para un usuario.
     * Reemplaza SP_OBTENER_EVIDENCIAS_PROXIMAS.
     */
    public function getUpcomingEvidences(int $usuarioId, string $limitDate)
    {
        return EvidenceAssignment::with(['evidence.criterion', 'user'])
            ->where('usuario_id', $usuarioId)
            ->whereIn('estado', [
                EvidenceAssignment::ESTADO_PENDIENTE,
                EvidenceAssignment::ESTADO_EN_PROGRESO,
            ])
            ->where('fecha_limite', '<=', $limitDate)
            ->orderBy('fecha_limite', 'asc')
            ->get();
    }
}