<?php

namespace App\Services;

use App\Models\ExtensionRequest;
use App\Models\User;
use App\Notifications\ExtensionRequestCreated;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Pagination\LengthAwarePaginator;
use Carbon\Carbon;

/**
 * Servicio para solicitudes de ampliacion de plazo.
 * Usa stored procedures para todas las operaciones de base de datos.
 */
class ExtensionRequestService
{
    /**
     * Obtener todas las solicitudes con filtros y paginacion.
     */
    public function getAll(array $filters = [])
    {
        $perPage     = min($filters['per_page'] ?? 15, 100);
        $page        = $filters['page'] ?? 1;
        $offset      = ($page - 1) * $perPage;
        $estado      = $filters['estado'] ?? null;
        $usuarioId   = $filters['usuario_id'] ?? null;
        $asignacionId= $filters['evidencia_asignacion_id'] ?? null;
        $fechaDesde  = $filters['fecha_desde'] ?? null;
        $fechaHasta  = $filters['fecha_hasta'] ?? null;

        $total = DB::select('CALL SP_CONTAR_SOLICITUDES_AMPLIACION(?, ?, ?, ?, ?)', [
            $estado, $usuarioId, $asignacionId, $fechaDesde, $fechaHasta,
        ])[0]->total ?? 0;

        $rows = DB::select('CALL SP_OBTENER_SOLICITUDES_AMPLIACION(?, ?, ?, ?, ?, ?, ?)', [
            $estado, $usuarioId, $asignacionId, $fechaDesde, $fechaHasta,
            $offset, $perPage,
        ]);

        $items = ExtensionRequest::hydrate(array_map(fn($r) => (array) $r, $rows));

        return new LengthAwarePaginator($items, (int) $total, $perPage, $page, [
            'path' => request()->url(),
        ]);
    }

    /**
     * Obtener solicitudes pendientes.
     */
    public function getPending(array $filters = [])
    {
        $filters['estado'] = ExtensionRequest::ESTADO_PENDIENTE;
        return $this->getAll($filters);
    }

    /**
     * Obtener solicitudes de un usuario especifico.
     */
    public function getByUser(int $usuarioId, array $filters = [])
    {
        $filters['usuario_id'] = $usuarioId;
        return $this->getAll($filters);
    }

    /**
     * Encontrar solicitud por ID.
     */
    public function findById(int $id): ?ExtensionRequest
    {
        $rows = DB::select('CALL SP_BUSCAR_SOLICITUD_AMPLIACION(?)', [$id]);
        return $rows ? ExtensionRequest::hydrate(array_map(fn($r) => (array) $r, $rows))->first() : null;
    }

    /**
     * Crear una nueva solicitud de ampliacion.
     * Mantiene logica de notificacion por email a encargados de acreditacion (HU-16).
     */
    public function createRequest(array $data, int $usuarioId): ExtensionRequest
    {
        DB::beginTransaction();
        try {
            // Verificar que la asignacion existe
            $asignacion = DB::select('CALL SP_BUSCAR_ASIGNACION_EVIDENCIA(?)', [
                $data['evidencia_asignacion_id'],
            ]);
            if (empty($asignacion)) {
                throw new \Exception('La asignacion de evidencia no existe.');
            }

            // Verificar que no tenga solicitud pendiente para esta asignacion
            $pendiente = DB::select('CALL SP_VERIFICAR_SOLICITUD_PENDIENTE(?)', [
                $data['evidencia_asignacion_id'],
            ]);
            if (!empty($pendiente) && $pendiente[0]->existe) {
                throw new \Exception('Ya existe una solicitud pendiente para esta asignacion.');
            }

            // Crear la solicitud
            $rows = DB::select('CALL SP_CREAR_SOLICITUD_AMPLIACION(?, ?, ?, ?, ?)', [
                $data['evidencia_asignacion_id'],
                $usuarioId,
                $data['motivo'],
                $data['fecha_sugerida'],
                ExtensionRequest::ESTADO_PENDIENTE,
            ]);

            $extensionRequest = ExtensionRequest::hydrate(array_map(fn($r) => (array) $r, $rows))->first();

            // HU-16: Notificacion a encargados de acreditacion de la carrera
            try {
                $asignacionData = $asignacion[0];
                // Cargar la cadena de relaciones para obtener la carrera
                $assignment = \App\Models\EvidenceAssignment::with(
                    'process.accreditationCycle.careerCampus.career'
                )->find($data['evidencia_asignacion_id']);

                if ($assignment) {
                    $careerId = $assignment->process->accreditationCycle->careerCampus->carrera_id ?? null;

                    $managers = User::whereHas('roles', fn($q) => $q->where('name', 'Encargado de Acreditacion'))
                        ->when($careerId, fn($q) => $q->whereHas('careers', fn($q2) => $q2->where('carrera_id', $careerId)))
                        ->get();

                    if ($managers->isEmpty() && $careerId) {
                        $managers = User::whereHas('roles', fn($q) => $q->where('name', 'Encargado de Acreditacion'))->get();
                    }

                    if ($managers->count() > 0) {
                        Notification::send($managers, new ExtensionRequestCreated($extensionRequest));
                    }
                }
            } catch (\Exception $n) {
                Log::warning('No se pudo enviar notificacion de solicitud de ampliacion', [
                    'solicitud_id' => $extensionRequest->solicitud_ampliacion_id,
                    'error'        => $n->getMessage(),
                ]);
            }

            DB::commit();
            return $extensionRequest;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Aprobar una solicitud de ampliacion.
     * El SP actualiza tambien la fecha_limite de la asignacion.
     */
    public function approve(int $solicitudId, int $resolutorId, ?string $justificacion = null): ExtensionRequest
    {
        DB::beginTransaction();
        try {
            $solicitud = DB::select('CALL SP_BUSCAR_SOLICITUD_AMPLIACION(?)', [$solicitudId]);
            if (empty($solicitud)) {
                throw new \Exception('La solicitud no existe.');
            }

            $s = $solicitud[0];
            if ($s->estado !== ExtensionRequest::ESTADO_PENDIENTE) {
                throw new \Exception('Solo se pueden aprobar solicitudes pendientes.');
            }

            $rows = DB::select('CALL SP_APROBAR_SOLICITUD_AMPLIACION(?, ?, ?, ?, ?)', [
                $solicitudId,
                $resolutorId,
                $justificacion,
                Carbon::now()->format('Y-m-d H:i:s'),
                $s->fecha_sugerida,
            ]);

            DB::commit();
            return ExtensionRequest::hydrate(array_map(fn($r) => (array) $r, $rows))->first();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Rechazar una solicitud de ampliacion.
     */
    public function reject(int $solicitudId, int $resolutorId, string $justificacion): ExtensionRequest
    {
        DB::beginTransaction();
        try {
            $solicitud = DB::select('CALL SP_BUSCAR_SOLICITUD_AMPLIACION(?)', [$solicitudId]);
            if (empty($solicitud)) {
                throw new \Exception('La solicitud no existe.');
            }

            $s = $solicitud[0];
            if ($s->estado !== ExtensionRequest::ESTADO_PENDIENTE) {
                throw new \Exception('Solo se pueden rechazar solicitudes pendientes.');
            }

            $rows = DB::select('CALL SP_RECHAZAR_SOLICITUD_AMPLIACION(?, ?, ?, ?)', [
                $solicitudId,
                $resolutorId,
                $justificacion,
                Carbon::now()->format('Y-m-d H:i:s'),
            ]);

            DB::commit();
            return ExtensionRequest::hydrate(array_map(fn($r) => (array) $r, $rows))->first();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}