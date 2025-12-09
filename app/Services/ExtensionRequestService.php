<?php

namespace App\Services;

use App\Models\ExtensionRequest;
use App\Models\EvidenceAssignment;
use App\Models\User;
use App\Notifications\ExtensionRequestCreated; // HU-16: NOTIFICACIÓN - Importar clase
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification; // HU-16: NOTIFICACIÓN - Importar facade
use Illuminate\Support\Facades\Log; // HU-16: NOTIFICACIÓN - Para logs de errores
use Carbon\Carbon;

/**
 * Service para manejar la lógica de negocio de solicitudes de ampliación.
 * 
 * Centraliza operaciones como crear, aprobar y rechazar solicitudes.
 */
class ExtensionRequestService
{
    /**
     * Obtener todas las solicitudes con sus relaciones.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAll()
    {
        return ExtensionRequest::with(['evidenceAssignment', 'user', 'resolutor'])
            ->orderBy('fecha_solicitud', 'desc')
            ->get();
    }

    /**
     * Obtener solicitudes pendientes.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getPending()
    {
        return ExtensionRequest::with(['evidenceAssignment', 'user'])
            ->pendientes()
            ->orderBy('fecha_solicitud', 'asc')
            ->get();
    }

    /**
     * Obtener solicitudes de un usuario específico.
     *
     * @param int $usuarioId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getByUser(int $usuarioId)
    {
        return ExtensionRequest::with(['evidenceAssignment', 'resolutor'])
            ->deUsuario($usuarioId)
            ->orderBy('fecha_solicitud', 'desc')
            ->get();
    }

    /**
     * Encontrar solicitud por ID.
     *
     * @param int $id
     * @return ExtensionRequest|null
     */
    public function findById(int $id): ?ExtensionRequest
    {
        return ExtensionRequest::with(['evidenceAssignment', 'user', 'resolutor'])->find($id);
    }

    /**
     * Crear una nueva solicitud de ampliación.
     *
     * @param array $data
     * @param int $usuarioId ID del usuario solicitante
     * @return ExtensionRequest
     * @throws \Exception
     */
    public function createRequest(array $data, int $usuarioId): ExtensionRequest
    {
        DB::beginTransaction();
        try {
            // Verificar que la asignación existe
            $asignacion = EvidenceAssignment::find($data['evidencia_asignacion_id']);
            if (!$asignacion) {
                throw new \Exception('La asignación de evidencia no existe.');
            }

            // TEMPORAL: Comentado para pruebas sin autenticación
            // Verificar que el usuario es el asignado
            // if ($asignacion->usuario_id !== $usuarioId) {
            //     throw new \Exception('Solo el usuario asignado puede solicitar ampliación.');
            // }

            // Verificar que no tenga una solicitud pendiente para esta asignación
            $solicitudPendiente = ExtensionRequest::where('evidencia_asignacion_id', $data['evidencia_asignacion_id'])
                ->where('estado', ExtensionRequest::ESTADO_PENDIENTE)
                ->exists();

            if ($solicitudPendiente) {
                throw new \Exception('Ya existe una solicitud pendiente para esta asignación.');
            }

            // Crear la solicitud
            $solicitud = ExtensionRequest::create([
                'evidencia_asignacion_id' => $data['evidencia_asignacion_id'],
                'usuario_id' => $usuarioId,
                'fecha_solicitud' => Carbon::now(),
                'motivo' => $data['motivo'],
                'fecha_sugerida' => $data['fecha_sugerida'],
                'estado' => ExtensionRequest::ESTADO_PENDIENTE,
            ]);

            // ========== HU-16: NOTIFICACIÓN - INICIO ==========
            // Enviar notificación a los encargados de acreditación
            // PARA DESACTIVAR: Comenta desde aquí hasta "NOTIFICACIÓN - FIN"
            try {
                // Obtener todos los encargados de acreditación usando whereHas
                // Nota: El rol se llama "Encargado de Acreditación" en el seeder
                // IMPORTANTE: Spatie usa la columna 'name', no 'nombre'
                $encargados = User::whereHas('roles', function ($query) {
                    $query->where('name', 'Encargado de Acreditación');
                })->get();
                
                // Solo enviar si hay encargados
                if ($encargados->count() > 0) {
                    Notification::send($encargados, new ExtensionRequestCreated($solicitud));
                } else {
                    Log::info('No hay usuarios con rol "Encargado de Acreditación" para notificar');
                }
            } catch (\Exception $notificationException) {
                // Si falla el envío de emails, no afecta la creación de la solicitud
                // Solo registramos el error en logs
                Log::warning('No se pudo enviar notificación de solicitud de ampliación', [
                    'solicitud_id' => $solicitud->solicitud_ampliacion_id,
                    'error' => $notificationException->getMessage()
                ]);
            }
            // ========== HU-16: NOTIFICACIÓN - FIN ==========

            DB::commit();
            return $solicitud->load(['evidenceAssignment', 'user']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Aprobar una solicitud de ampliación.
     *
     * @param int $solicitudId
     * @param int $resolutorId ID del usuario que aprueba
     * @param string|null $justificacion
     * @return ExtensionRequest
     * @throws \Exception
     */
    public function approve(int $solicitudId, int $resolutorId, ?string $justificacion = null): ExtensionRequest
    {
        DB::beginTransaction();
        try {
            $solicitud = ExtensionRequest::find($solicitudId);
            if (!$solicitud) {
                throw new \Exception('La solicitud no existe.');
            }

            if ($solicitud->estado !== ExtensionRequest::ESTADO_PENDIENTE) {
                throw new \Exception('Solo se pueden aprobar solicitudes pendientes.');
            }

            // Actualizar la solicitud
            $solicitud->update([
                'estado' => ExtensionRequest::ESTADO_APROBADA,
                'fecha_resolucion' => Carbon::now(),
                'usuario_resolutor_id' => $resolutorId,
                'justificacion' => $justificacion,
            ]);

            // Actualizar la fecha límite de la asignación de evidencia
            $asignacion = $solicitud->evidenceAssignment;
            $asignacion->update([
                'fecha_limite' => $solicitud->fecha_sugerida,
            ]);

            DB::commit();
            return $solicitud->load(['evidenceAssignment', 'user', 'resolutor']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Rechazar una solicitud de ampliación.
     *
     * @param int $solicitudId
     * @param int $resolutorId ID del usuario que rechaza
     * @param string $justificacion
     * @return ExtensionRequest
     * @throws \Exception
     */
    public function reject(int $solicitudId, int $resolutorId, string $justificacion): ExtensionRequest
    {
        DB::beginTransaction();
        try {
            $solicitud = ExtensionRequest::find($solicitudId);
            if (!$solicitud) {
                throw new \Exception('La solicitud no existe.');
            }

            if ($solicitud->estado !== ExtensionRequest::ESTADO_PENDIENTE) {
                throw new \Exception('Solo se pueden rechazar solicitudes pendientes.');
            }

            // Actualizar la solicitud
            $solicitud->update([
                'estado' => ExtensionRequest::ESTADO_RECHAZADA,
                'fecha_resolucion' => Carbon::now(),
                'usuario_resolutor_id' => $resolutorId,
                'justificacion' => $justificacion,
            ]);

            DB::commit();
            return $solicitud->load(['evidenceAssignment', 'user', 'resolutor']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
