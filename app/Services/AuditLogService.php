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
}
