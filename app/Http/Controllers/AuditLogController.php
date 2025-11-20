<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use App\Services\AuditLogService;
use App\Http\Requests\AuditLogIndexRequest;
use App\Http\Resources\AuditLogResource;

class AuditLogController extends Controller
{
    public function __construct(private AuditLogService $auditLogService) {}

    /**
     * Listar registros de bitácora con filtros opcionales.
     */
    public function index(AuditLogIndexRequest $request)
    {
        $auditLogs = $this->auditLogService->list($request->validated());
        
        return AuditLogResource::collection($auditLogs);
    }

    /**
     * Mostrar detalle de un registro específico de bitácora.
     */
    public function show(AuditLog $auditLog)
    {
        // Cargar relaciones
        $auditLog->load(['user', 'actionType']);
        
        // Retornar con Resource
        return new AuditLogResource($auditLog);
    }
}
