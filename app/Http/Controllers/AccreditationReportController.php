<?php

namespace App\Http\Controllers;

use App\Http\Requests\ListAccreditationReportsRequest;
use App\Http\Requests\PublishAccreditationReportRequest;
use App\Http\Requests\UnpublishAccreditationReportRequest;
use App\Http\Requests\UpdateAccreditationReportRequest;
use App\Http\Resources\AccreditationReportResource;
use App\Models\AccreditationCycle;
use App\Models\AccreditationReport;
use App\Services\AccreditationReportService;
use App\Services\AuditLogService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

/**
 * Controlador de Informes de Acreditación.
 *
 * HU-027: Publicación de informe de acreditación aprobado.
 *
 * Rutas:
 *   GET    /api/informes-acreditacion              → index()         (público)
 *   GET    /api/ciclos/{cycle}/informe             → showByCycle()   (público)
 *   POST   /api/ciclos/{cycle}/informe             → publish()       (auth + permiso)
 *   PATCH  /api/informes-acreditacion/{report}/despublicar → unpublish() (auth + permiso)
 */
class AccreditationReportController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly AccreditationReportService $service
    ) {}

    // -----------------------------------------------------------------------
    // Lectura pública (sin autenticación)
    // -----------------------------------------------------------------------

    /**
     * GET /api/informes-acreditacion
     * Lista paginada de informes publicados, accesible sin autenticación.
     * Filtros opcionales (query string): carrera_id, sede_id, per_page (máx. 25).
     */
    public function index(ListAccreditationReportsRequest $request)
    {
        $filters = $request->validated();
        $filters['per_page'] = min((int) ($filters['per_page'] ?? 15), 25); // máx. 25: endpoint público con eager loading, programas SINAES activos no superan ~30

        $reports = $this->service->getPublicReports($filters);

        return AccreditationReportResource::collection($reports);
    }

    /**
     * GET /api/admin/informes-acreditacion
     * Lista administrativa de informes.
     *
     * Permite incluir informes despublicados mediante include_unpublished=true.
     * Requiere autenticación y permiso informes_acreditacion.view.
     */
    public function indexAdmin(ListAccreditationReportsRequest $request)
    {
        $filters = $request->validated();
        $filters['per_page'] = min((int) ($filters['per_page'] ?? 15), 50);

        $reports = $this->service->getAdminReports($filters);

        return AccreditationReportResource::collection($reports);
    }

    /**
     * GET /api/ciclos/{cycle}/informe
     * Devuelve el informe del ciclo indicado.
     * Si el informe está publicado, es accesible sin autenticación.
     * Si está despublicado, requiere permiso informes_acreditacion.view.
     */
    public function showByCycle(AccreditationCycle $cycle)
    {
        $report = $this->service->getReportByCycle($cycle);

        if (!$report) {
            return response()->json(['message' => 'Este ciclo aún no tiene un informe de acreditación.'], 404);
        }

        // Solo requiere autorización si el informe no está publicado
        // Un informe publicado es de acceso libre (HU-027)
        if (!$report->isPublished()) {
            $this->authorize('view', $report);
        }

        return new AccreditationReportResource(
            $report->loadMissing(['accreditationCycle.careerCampus.career', 'accreditationCycle.careerCampus.campus', 'file', 'publishedBy'])
        );
    }

    // -----------------------------------------------------------------------
    // Escritura (autenticación requerida)
    // -----------------------------------------------------------------------

    /**
     * POST /api/ciclos/{cycle}/informe
     * Publica el informe de acreditación de un ciclo completado.
     * El ciclo llega resuelto por Route Model Binding ({cycle}).
     */
    public function publish(PublishAccreditationReportRequest $request, AccreditationCycle $cycle)
    {
        $this->authorize('publish', [AccreditationReport::class, $cycle]);

        try {
            $data = $request->validated();
            $data['archivo'] = $request->file('archivo');
            $report = $this->service->publishReport(
                $cycle,
                $data,
                $request->user()
            );
        } catch (\InvalidArgumentException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        AuditLogService::log(
            'publicar',
            "Se publicó el informe de acreditación del ciclo \"{$cycle->nombre}\" (resolución: {$report->numero_resolucion}).",
            'Informe Acreditación'
        );

        $this->service->notifyPublication($cycle, $report, $request->user());

        return AccreditationReportResource::make(
            $report->loadMissing(['accreditationCycle.careerCampus.career', 'accreditationCycle.careerCampus.campus', 'file', 'publishedBy'])
        )->response()->setStatusCode(201);
    }

    /**
     * PUT /api/informes-acreditacion/{report}
     * Edita los datos de un informe (publicado o despublicado).
     * Permite corregir número de resolución, fechas, archivo o reeemplazar el PDF.
     */
    public function update(UpdateAccreditationReportRequest $request, AccreditationReport $report)
    {
        $this->authorize('update', $report);

        $data = $request->validated();
        if ($request->hasFile('archivo')) {
            $data['archivo'] = $request->file('archivo');
        }
        $updated = $this->service->updateReport($report, $data, $request->user());

        AuditLogService::log(
            'editar',
            "Se editó el informe de acreditación del ciclo \"{$updated->accreditationCycle->nombre}\" (resolución: {$updated->numero_resolucion}).",
            'Informe Acreditación'
        );

        return new AccreditationReportResource($updated);
    }

    /**
     * DELETE /api/informes-acreditacion/{report}
     * Elimina físicamente el informe. Solo Superusuario.
     * Libera la restricción UNIQUE del ciclo para poder volver a publicar.
     */
    public function destroy(AccreditationReport $report)
    {
        $this->authorize('delete', $report);

        $cicloNombre      = $report->accreditationCycle->nombre;
        $numeroResolucion = $report->numero_resolucion;

        $this->service->deleteReport($report);

        AuditLogService::log(
            'eliminar',
            "Se eliminó el informe de acreditación del ciclo \"{$cicloNombre}\" (resolución: {$numeroResolucion}).",
            'Informe Acreditación'
        );

        return response()->json(['message' => 'Informe eliminado correctamente.']);
    }

    /**
     * PATCH /api/informes-acreditacion/{report}/despublicar
     * Despublica el informe y revoca el acceso público al PDF.
     */
    public function unpublish(UnpublishAccreditationReportRequest $request, AccreditationReport $report)
    {
        $this->authorize('unpublish', $report);

        try {
            $data = $request->validated();
            $updated = $this->service->unpublishReport(
                $report,
                $data['motivo'] ?? null
            );
        } catch (\InvalidArgumentException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        AuditLogService::log(
            'despublicar',
            "Se despublicó el informe de acreditación del ciclo \"{$updated->accreditationCycle->nombre}\" (resolución: {$updated->numero_resolucion}).",
            'Informe Acreditación'
        );

        $this->service->notifyUnpublication($updated, $request->user());

        return new AccreditationReportResource(
            $updated->loadMissing(['accreditationCycle', 'file', 'publishedBy'])
        );
    }
}
