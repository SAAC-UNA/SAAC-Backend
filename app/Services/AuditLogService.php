<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\ActionType;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Pagination\LengthAwarePaginator;

class AuditLogService
{
    /**
     * Registra una accion en la bitacora del sistema.
     */
    public static function log(
        string $actionName,
        ?string $detail = null,
        ?string $modulo = null,
        ?int $userId = null
    ): bool {
        try {
            $userId = $userId ?? Auth::id();

            // Buscar tipo de accion por descripcion
            $rows = DB::select('CALL SP_BUSCAR_TIPO_ACCION_POR_NOMBRE(?)', [$actionName]);
            if (empty($rows)) {
                Log::warning("Tipo de accion '{$actionName}' no encontrado en catalogo");
                return false;
            }
            $tipoAccionId = $rows[0]->tipo_accion_id;

            DB::statement('CALL SP_REGISTRAR_ACCION(?, ?, ?, ?, ?)', [
                $userId,
                $tipoAccionId,
                $modulo,
                $detail,
                now()->format('Y-m-d H:i:s'),
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Error al registrar en bitacora', [
                'action' => $actionName,
                'module' => $modulo,
                'error'  => $e->getMessage(),
                'user'   => Auth::id(),
            ]);
            return false;
        }
    }

    /**
     * Listar registros de bitacora con filtros opcionales y paginacion.
     */
    public function list(array $filters = [])
    {
        $perPage     = $filters['per_page'] ?? 15;
        $page        = $filters['page'] ?? 1;
        $offset      = ($page - 1) * $perPage;
        $usuarioId   = $filters['usuario_id'] ?? null;
        $modulo      = $filters['modulo'] ?? null;
        $fechaDesde  = $filters['fecha_desde'] ?? null;
        $fechaHasta  = $filters['fecha_hasta'] ?? null;

        // Resolver tipo_accion_id desde nombre si viene como texto
        $tipoAccionId = $filters['tipo_accion_id'] ?? null;
        if (!$tipoAccionId && !empty($filters['tipo_accion'])) {
            $rows = DB::select('CALL SP_BUSCAR_TIPO_ACCION_POR_NOMBRE(?)', [$filters['tipo_accion']]);
            $tipoAccionId = $rows[0]->tipo_accion_id ?? null;
        }

        $total = DB::select('CALL SP_CONTAR_BITACORA(?, ?, ?, ?, ?)', [
            $usuarioId,
            $tipoAccionId,
            $modulo,
            $fechaDesde,
            $fechaHasta,
        ])[0]->total ?? 0;

        $rows = DB::select('CALL SP_OBTENER_BITACORA(?, ?, ?, ?, ?, ?, ?)', [
            $usuarioId,
            $tipoAccionId,
            $modulo,
            $fechaDesde,
            $fechaHasta,
            $offset,
            $perPage,
        ]);

        $items = AuditLog::hydrate(array_map(fn($r) => (array) $r, $rows));

        return new LengthAwarePaginator($items, (int) $total, $perPage, $page, [
            'path' => request()->url(),
        ]);
    }

    /**
     * Obtener la lista de modulos registrados en la bitacora.
     */
    public function getModules()
    {
        $rows = DB::select('CALL SP_OBTENER_MODULOS_BITACORA()');
        return collect($rows)->pluck('modulo');
    }

    /**
     * Obtener la lista de tipos de accion.
     */
    public function getActionTypes()
    {
        $rows = DB::select('CALL SP_OBTENER_TIPOS_ACCION()');
        return ActionType::hydrate(array_map(fn($r) => (array) $r, $rows));
    }

    /**
     * Obtener registros de bitacora para exportacion en un rango de fechas.
     */
    public function getForExport(string $desde, string $hasta)
    {
        $exportSafetyLimit = config('saac.export_limit', 20000);

        $count = DB::select('CALL SP_CONTAR_BITACORA_EXPORTACION(?, ?)', [$desde, $hasta])[0]->total ?? 0;

        if ($count > $exportSafetyLimit) {
            throw new \Exception(
                "El rango seleccionado contiene $count registros. " .
                "El maximo permitido para exportacion es $exportSafetyLimit. " .
                "Reduzca el rango de fechas y vuelva a intentarlo."
            );
        }

        $rows = DB::select('CALL SP_OBTENER_BITACORA_EXPORTACION(?, ?)', [$desde, $hasta]);
        return AuditLog::hydrate(array_map(fn($r) => (array) $r, $rows));
    }
}