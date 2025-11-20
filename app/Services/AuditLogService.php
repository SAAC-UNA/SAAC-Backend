<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\ActionType;
use Illuminate\Support\Facades\Auth;

class AuditLogService
{
    /**
     * Registra una acción en la bitácora del sistema.
     *
     * @param string $actionName  Nombre de la acción (crear, editar, eliminar, consultar, login, logout)
     * @param string|null $detail Detalle opcional de la acción
     */
    public static function log(string $actionName, ?string $detail = null): void
    {
        try {
            // ID del usuario autenticado (puede ser null en login_failed)
            $userId = Auth::id();

            // Buscar el tipo de acción por descripción
            $actionType = ActionType::where('descripcion', $actionName)->first();

            if (!$actionType) {
                // Si no existe el tipo de acción, no registrar nada
                return;
            }

            // Registrar en la bitácora
            AuditLog::create([
                'usuario_id'     => $userId,
                'tipo_accion_id' => $actionType->tipo_accion_id,
                'detalle'        => $detail,
                'fecha_hora'     => now(),
            ]);
        } catch (\Exception $e) {
            // Si ocurre un error, no romper la aplicación
            // Opcional: Log::error('Error en AuditLog: ' . $e->getMessage());
        }
    }

    /**
     * Listar registros de bitácora con filtros opcionales.
     *
     * @param array $filters Filtros opcionales (usuario_id, tipo_accion_id, fecha_desde, fecha_hasta)
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function list(array $filters = [])
    {
        $query = AuditLog::query();

        // Filtro por usuario
        if (!empty($filters['usuario_id'])) {
            $query->where('usuario_id', $filters['usuario_id']);
        }

        // Filtro por tipo de acción
        if (!empty($filters['tipo_accion_id'])) {
            $query->where('tipo_accion_id', $filters['tipo_accion_id']);
        }

        // Filtro por rango de fechas
        if (!empty($filters['fecha_desde'])) {
            $query->where('fecha_hora', '>=', $filters['fecha_desde']);
        }

        if (!empty($filters['fecha_hasta'])) {
            $query->where('fecha_hora', '<=', $filters['fecha_hasta']);
        }

        // Incluir relaciones
        $query->with(['user', 'actionType']);

        // Ordenar por fecha descendente
        $query->orderBy('fecha_hora', 'desc');

        // Paginar resultados
        return $query->paginate(15);
    }
}
