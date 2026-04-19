<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\ActionType;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
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

            // Cache del catálogo de tipos de acción (raramente cambia)
            $actionTypes = Cache::remember('audit_action_types', 3600, fn() =>
                ActionType::pluck('tipo_accion_id', 'descripcion')->all()
            );

            $tipoAccionId = $actionTypes[$actionName] ?? null;

            if (!$tipoAccionId) {
                Log::warning("Tipo de accion '{$actionName}' no encontrado en catalogo");
                return false;
            }

            AuditLog::create([
                'usuario_id'     => $userId,
                'tipo_accion_id' => $tipoAccionId,
                'modulo'         => $modulo,
                'detalle'        => $detail,
                'fecha_hora'     => now(),
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
        $perPage    = $filters['per_page'] ?? 15;
        $usuarioId  = $filters['usuario_id'] ?? null;
        $modulo      = $filters['modulo'] ?? null;
        $fechaDesde  = $filters['fecha_desde'] ?? null;
        $fechaHasta  = $filters['fecha_hasta'] ?? null;
        $search      = $filters['search'] ?? null;

        $tipoAccionId = $filters['tipo_accion_id'] ?? null;
        if (!$tipoAccionId && !empty($filters['tipo_accion'])) {
            $tipoAccionId = ActionType::where('descripcion', $filters['tipo_accion'])
                ->value('tipo_accion_id');
        }

        return AuditLog::with(['user.roles', 'actionType'])
            ->when($usuarioId,    fn($q) => $q->where('usuario_id', $usuarioId))
            ->when($tipoAccionId, fn($q) => $q->where('tipo_accion_id', $tipoAccionId))
            ->when($modulo,       fn($q) => $q->where('modulo', 'like', "%{$modulo}%"))
            ->when($fechaDesde,   fn($q) => $q->where('fecha_hora', '>=', $fechaDesde))
            ->when($fechaHasta,   fn($q) => $q->where('fecha_hora', '<=', $fechaHasta))
            ->when($search,        fn($q) => $q->where(fn($inner) =>
                $inner->whereHas('user', fn($u) =>
                    $u->where('nombre', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                )
                ->orWhereHas('actionType', fn($a) =>
                    $a->where('descripcion', 'like', "%{$search}%")
                )
                ->orWhere('BITACORA.modulo', 'like', "%{$search}%")
                ->orWhere('BITACORA.detalle', 'like', "%{$search}%")
            ))
            ->orderBy('fecha_hora', 'desc')
            ->paginate($perPage);
    }

    /**
     * Obtener la lista de modulos registrados en la bitacora.
     */
    public function getModules()
    {
        return Cache::remember('bitacora_modulos', 300, fn() =>
            AuditLog::select('modulo')
                ->whereNotNull('modulo')
                ->distinct()
                ->orderBy('modulo')
                ->pluck('modulo')
        );
    }

    /**
     * Obtener la lista de tipos de accion.
     */
    public function getActionTypes()
    {
        return ActionType::orderBy('descripcion')->get()->map(function ($type) {
            return [
                'tipo_accion_id' => $type->tipo_accion_id,
                'descripcion'    => $type->descripcion,
                'label'          => ucfirst(str_replace('_', ' ', $type->descripcion)),
            ];
        });
    }

    /**
     * Obtener registros de bitacora para exportacion en un rango de fechas.
     */
    public function getForExport(string $desde, string $hasta)
    {
        $exportSafetyLimit = config('saac.export_limit', 20000);

        $count = AuditLog::whereBetween('fecha_hora', [$desde, $hasta])->count();

        if ($count > $exportSafetyLimit) {
            throw new \Exception(
                "El rango seleccionado contiene $count registros. " .
                "El maximo permitido para exportacion es $exportSafetyLimit. " .
                "Reduzca el rango de fechas y vuelva a intentarlo."
            );
        }

        return AuditLog::with(['user.roles', 'actionType'])
            ->whereBetween('fecha_hora', [$desde, $hasta])
            ->orderBy('fecha_hora', 'desc')
            ->get();
    }
}