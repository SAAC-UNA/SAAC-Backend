<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use App\Services\AuditLogService;
use App\Http\Requests\AuditLogIndexRequest;
use App\Http\Requests\AuditLogExportRequest;
use App\Http\Resources\AuditLogResource;
use App\Services\ExcelExportService;
use Barryvdh\DomPDF\Facade\Pdf;

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
     /**
     * Obtener la lista de módulos registrados en la bitácora.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getModules()
    {
        $modulos = $this->auditLogService->getModules();

        return response()->json($modulos);
    }
    // metodo de exportar bitacora a PDF o Excel
    public function export(AuditLogExportRequest $request, ExcelExportService $excelExportService)
    {
        //  Datos ya vienen validados por el FormRequest
        $data = $request->validated();

        $fechaDesde = $data['fecha_desde'];
        $fechaHasta = $data['fecha_hasta'];
        $format     = $data['format'] ?? 'pdf';

        //  Pedimos al servicio SOLO los registros dentro del rango
        $logs = $this->auditLogService->getForExport($fechaDesde, $fechaHasta);

        // Excel
        if ($format === 'excel') {
            $filePath = $excelExportService->generateAuditLogExcel($logs);
            return response()->download($filePath)->deleteFileAfterSend();
        }

        // PDF
        $pdf = Pdf::loadView('bitacora-pdf', ['logs' => $logs]);
        return $pdf->download('audit_log.pdf');
    }
}
