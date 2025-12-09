<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\ActionType;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AuditLogService
{
    /**
     * Registra una acción en la bitácora del sistema.
     *
     * @param string $actionName  Nombre de la acción (crear, editar, eliminar, consultar, login, logout)
     * @param string|null $detail Detalle opcional de la acción
     * @param string|null $modulo Módulo del sistema donde ocurrió la acción
     * @return bool Retorna true si se registró exitosamente, false si falló
     */
    public static function log(string $actionName, ?string $detail = null, ?string $modulo = null): bool
    {
        try {
            // ID del usuario autenticado (puede ser null en login_failed)
            $userId = Auth::id();

            // Buscar el tipo de acción por descripción
            $actionType = ActionType::where('descripcion', $actionName)->first();

            if (!$actionType) {
                // Si no existe el tipo de acción, registrar en log y retornar false
                Log::warning("Tipo de acción '{$actionName}' no encontrado en catálogo");
                return false;
            }

            // Registrar en la bitácora
            AuditLog::create([
                'usuario_id'     => $userId,
                'tipo_accion_id' => $actionType->tipo_accion_id,
                'modulo'         => $modulo,
                'detalle'        => $detail,
                'fecha_hora'     => now(),
            ]);

            return true;
        } catch (\Exception $e) {
            // Registrar error en log para que superusuario/admin técnico lo vea
            Log::error('Error al registrar en bitácora', [
                'action' => $actionName,
                'module' => $modulo,
                'error'  => $e->getMessage(),
                'user'   => Auth::id()
            ]);
            return false;
        }
    }

    /**
     * Listar registros de bitácora con filtros opcionales.
     *
     * @param array $filters Filtros opcionales (usuario_id, tipo_accion_id, modulo, fecha_desde, fecha_hasta)
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function list(array $filters = [])
    {
        $query = AuditLog::query();

        // Filtro por usuario
        if (!empty($filters['usuario_id'])) {
            $query->where('usuario_id', $filters['usuario_id']);
        }

        // Filtro por tipo de acción (acepta ID o nombre)
        if (!empty($filters['tipo_accion_id'])) {
            $query->where('tipo_accion_id', $filters['tipo_accion_id']);
        } elseif (!empty($filters['tipo_accion'])) {
            // Buscar el ID por nombre de acción
            $actionType = ActionType::where('descripcion', $filters['tipo_accion'])->first();
            if ($actionType) {
                $query->where('tipo_accion_id', $actionType->tipo_accion_id);
            }
        }

        // Filtro por módulo
        if (!empty($filters['modulo'])) {
            $query->where('modulo', $filters['modulo']);
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
        $perPage = $filters['per_page'] ?? 15;

        return $query->paginate($perPage);
    }
    /**
     * Obtener la lista de módulos registrados en la bitácora.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getModules()
    {
        return AuditLog::query()
            ->whereNotNull('modulo')
            ->distinct()
            ->orderBy('modulo')
            ->pluck('modulo');
    }
    /**
     * Obtener la lista de acciones registradas en la bitácora.
     */
        public function getActionTypes()
    {
        return ActionType::query()
            ->select('tipo_accion_id', 'descripcion')
            ->orderBy('descripcion')
            ->get();
    }
    /**
     * Obtener registros de bitácora para exportación en un rango de fechas.
     *
     * @param string $desde Fecha de inicio (YYYY-MM-DD)
     * @param string $hasta Fecha de fin (YYYY-MM-DD)
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getForExport(string $desde, string $hasta)
    {
        // límite máximo permitido para exportación, por medio de configuración
        $exportSafetyLimit = config('saac.export_limit', 20000);// quitar hardcodeo

         // Contar registros en el rango solicitado 

        $count = AuditLog::whereBetween('fecha_hora', [$desde, $hasta])->count();

        if ($count > $exportSafetyLimit) {
            throw new \Exception(
                "El rango seleccionado contiene $count registros. ".
                "El máximo permitido para exportación es $exportSafetyLimit. ".
                "Reduzca el rango de fechas y vuelva a intentarlo."
            );
        }

        return AuditLog::with(['user', 'actionType'])
            ->whereBetween('fecha_hora', [$desde, $hasta])
            ->orderBy('fecha_hora', 'desc')
            ->get();
    }


}
