<?php

namespace App\Http\Controllers;

use App\Models\Evidence;
use Illuminate\Database\QueryException;
use App\Http\Resources\EvidenceResource;
use App\Services\TradicionalEvidenceService;
use App\Services\TradicionalEvidenceFilterService;
use App\Http\Requests\EvidenceRequest;
use App\Http\Requests\FilterEvidenceRequest;
use App\Http\Requests\RetroalimentacionRequest;
use App\Services\AuditLogService;
use App\Services\EvidenceReportService;
use App\Exports\InformeExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;


class EvidenceController extends Controller
{
    protected TradicionalEvidenceService $service;
    protected TradicionalEvidenceFilterService $filterService;
    protected EvidenceReportService $reportService;

    public function __construct(
        TradicionalEvidenceService $service,
        TradicionalEvidenceFilterService $filterService,
        EvidenceReportService $reportService
    ) {
        $this->service       = $service;
        $this->filterService = $filterService;
        $this->reportService = $reportService;
    }
    /**
     * GET /api/estructura/evidencias
     */
    public function index()
    {
        $items = $this->service->getAll(); // antes: Evidence::orderBy(...)
        return EvidenceResource::collection($items)->response(); // 200
    }

    /**
     * GET /api/estructura/evidencias/{id}
     */
    public function show($id)
    {
        $evidence = $this->service->findById((int)$id); // antes: Evidence::find
        if (!$evidence) return response()->json(['message' => 'Evidencia no encontrada.'], 404);

        return EvidenceResource::make($evidence)->response(); // 200
    }

    /**
     * POST /api/estructura/evidencias
     */
    public function store(EvidenceRequest $request)
    {
        $evidence = $this->service->create($request->validated());

        return \App\Http\Resources\EvidenceResource::make($evidence)
            ->response()
            ->setStatusCode(201);
    }

    /**
     * PUT/PATCH /api/estructura/evidencias/{id}
     */
    public function update(EvidenceRequest $request, $id)
    {
        $evidence = \App\Models\Evidence::find($id);
        if (!$evidence) {
            return response()->json(['message' => 'Evidencia no encontrada.'], 404);
        }

        $updated = $this->service->update($evidence, $request->validated());

        return \App\Http\Resources\EvidenceResource::make($updated)
            ->response()
            ->setStatusCode(200);
    }
    /**
     * DELETE /api/estructura/evidencias/{id}
     */
    public function destroy($id)
    {
        $evidence = Evidence::find($id);
        if (!$evidence) {
            return response()->json(['message' => 'Evidencia no encontrada.'], 404);
        }

        try {
            $this->service->delete($evidence); // antes: $e->delete()

            return response()->noContent(); // 204
        } catch (QueryException $qe) {
            if ((int)($qe->errorInfo[1] ?? 0) === 1451) {
                return response()->json([
                    'message' => 'No se puede eliminar: la evidencia tiene registros relacionados.',
                    'code'    => 'FK_CONSTRAINT',
                ], 409);
            }
            Log::error('Error deleting evidence', ['error' => $qe->getMessage()]);
            return response()->json(['message' => 'Error al eliminar.'], 500);
        }
    }
    /**
     * POST /api/estructura/evidencias/{id}/retroalimentacion  (HU-013)
     *
     * Se usa POST (no PATCH) porque la operación NO es idempotente:
     * cada llamada crea un nuevo comentario en COMENTARIO además de
     * actualizar el estado. El servicio ejecuta en una transacción:
     *   - Cambia el estado a 'observada' o 'validada'
     *   - Guarda el comentario en COMENTARIO (relación polimórfica)
     *   - Registra la acción en BITACORA
     *   - Invalida el caché de lista
     */
    public function retroalimentar(RetroalimentacionRequest $request, $id)
    {
        // Verificar que el usuario tenga uno de los roles autorizados
        $user = $request->user();
        if (!$user->hasRole(['Encargado de Acreditación', 'Administrador', 'Superusuario'])) {
            return response()->json(['message' => 'No autorizado para retroalimentar evidencias.'], 403);
        }

        $evidence = $this->service->findById((int) $id);
        if (!$evidence) {
            return response()->json(['message' => 'Evidencia no encontrada.'], 404);
        }

        try {
            $evidence = $this->service->retroalimentar($evidence, $request->validated(), $user);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return EvidenceResource::make($evidence)->response();
    }

    /**
     * PATCH /api/estructura/evidencias/{id}/active
     * Body JSON: { "active": true }
     */
    public function setActive(\Illuminate\Http\Request $request, $id)
    {
        $evidence = Evidence::find($id);
        if (!$evidence) {
            return response()->json(['message' => 'Evidencia no encontrada.'], 404);
        }

        $validated = $request->validate([
            'active' => ['required', 'boolean'],
        ]);

        $evidence->activo = $validated['active'];
        $evidence->saveQuietly(); // observer omitido: el log manual cubre esta acción
        // Registro en el log de bitácora
        $previousStatus = $validated['active'] ? 'INACTIVA' : 'ACTIVA';
        $newStatus      = $validated['active'] ? 'ACTIVA' : 'INACTIVA';
        AuditLogService::log(
'editar',
    "Se actualizó el estado de la evidencia \"{$evidence->nombre}\". ".
            "Estado anterior: {$previousStatus}. Estado actual: {$newStatus}.",
    'Evidencia'
        );

        return response()->json([
            'message' => 'Estado de la evidencia actualizado correctamente.',
            'data'    => $evidence
        ], 200);
    }

    // ============================================================
    // MÉTODO NUEVO PARA HU-012: Filtrado Avanzado de Evidencias
    // ============================================================

    /**
     * GET /api/estructura/evidencias/filter
     *
     * MÉTODO NUEVO - Creado para HU-012
     * Este método NO modifica el comportamiento de index()
     *
     * Filtrar evidencias con múltiples criterios combinables
     *
     * Cumple con los siguientes Criterios de Aceptación:
     * - #1: Aplicación de filtros básicos
     * - #2: Validación de parámetros
     * - #3: Restricción según rol del usuario
     * - #4: Ordenamiento de resultados
     * - #5: Paginación de resultados
     * - #6: Sin coincidencias (retorna array vacío)
     *
     * Query parameters (todos opcionales):
     * ?criterio_id=4&responsable_id=15&fecha_desde=2025-01-01&fecha_hasta=2025-12-31
     * &estado_evidencia_id=2&rol_id=3&sort_by=fecha&sort_order=desc&per_page=20
     *
     * @param FilterEvidenceRequest $request Filtros validados
     * @return \Illuminate\Http\JsonResponse
     */
    public function filter(FilterEvidenceRequest $request)
    {
        $user    = $request->user();
        $filters = $request->validated();

        $paginatedResults = $this->filterService->filter($filters, $user);

        return EvidenceResource::collection($paginatedResults)->response();
    }

    /**
     * HU-012 - Exportar evidencias filtradas a Excel
     * GET /api/estructura/evidencias/export/excel?criterio_id=1&estado_evidencia_id=2...
     *
     * @param FilterEvidenceRequest $request
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function exportExcel(FilterEvidenceRequest $request)
    {
        $user = $request->user();
        $filters = $request->validated();

        try {
            $collection = $this->getExportCollection($filters, $user);
            $report = $this->reportService->build($collection, $filters);

            $exporter = new InformeExport($report);
            $filePath = $exporter->generate();
            $filename = 'informe_acreditacion_' . now()->format('Y-m-d_His') . '.xlsx';

            AuditLogService::log(
                'exportar',
                "Exportó evidencias en Excel con {$report['total_evidences']} registro(s).",
                'Reportes',
                $user?->usuario_id
            );

            return response()
                ->download(
                    $filePath,
                    $filename,
                    [
                        'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    ]
                )
                ->deleteFileAfterSend(true);
        } catch (\Throwable $exception) {
            Log::error('Error al generar informe de evidencias en Excel', [
                'user_id' => $user?->usuario_id,
                'error' => $exception->getMessage(),
            ]);

            return response()->json([
                'message' => 'No se pudo generar el informe en Excel.',
            ], 500);
        }
    }

    /**
     * HU-012 - Exportar evidencias filtradas a PDF
     * GET /api/estructura/evidencias/export/pdf?criterio_id=1&estado_evidencia_id=2...
     *
     * @param FilterEvidenceRequest $request
     * @return \Illuminate\Http\Response
     */
    public function exportPDF(FilterEvidenceRequest $request)
    {
        $user = $request->user();
        $filters = $request->validated();

        try {
            $collection = $this->getExportCollection($filters, $user);
            $report = $this->reportService->build($collection, $filters);

            $pdf = Pdf::loadView('exports.informe', ['report' => $report])
                ->setPaper('a4', 'landscape');

            $filename = 'evidencias_' . now()->format('Y-m-d_His') . '.pdf';

            AuditLogService::log(
                'exportar',
                "Exportó evidencias en PDF con {$report['total_evidences']} registro(s).",
                'Reportes',
                $user?->usuario_id
            );

            return $pdf->download($filename);
        } catch (\Throwable $exception) {
            Log::error('Error al generar informe de evidencias en PDF', [
                'user_id' => $user?->usuario_id,
                'error' => $exception->getMessage(),
            ]);

            return response()->json([
                'message' => 'No se pudo generar el informe en PDF.',
            ], 500);
        }
    }

    private function getExportCollection(array $filters, $user)
    {
        $filters['per_page'] = 999999;

        return collect($this->filterService->filter($filters, $user)->items());
    }
}
