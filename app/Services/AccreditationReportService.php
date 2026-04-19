<?php

namespace App\Services;

use App\Models\AccreditationCycle;
use App\Models\AccreditationReport;
use App\Models\File;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Servicio de Informes de Acreditación
 *
 * HU-028: Publicación de informe de acreditación aprobado.
 *
 * Responsabilidades:
 * - Publicar la resolución oficial de SINAES en un ciclo de acreditación.
 * - Despublicar el informe revocando el acceso público al archivo PDF.
 * - Consultar informes publicados (sin autenticación, acceso público).
 * - Resolver el informe asociado a un ciclo concreto.
 *
 * Regla de negocio clave: cada ciclo admite un único informe.
 * La constraint UNIQUE en ciclo_acreditacion_id lo garantiza a nivel de BD.
 *
 * El acceso público al archivo reutiliza el sistema de tokens de HU-023:
 * makePublic() → token UUID → ruta /p/{token} sin autenticación.
 */
class AccreditationReportService
{
    /**
     * FlexibleFileService se usa únicamente para acceder a makePublic() y
     * revokePublicAccess(), que son operaciones model-agnostic definidas
     * en AbstractFileService. Cualquier subclase concreta sirve aquí.
     */
    public function __construct(
        private readonly FlexibleFileService $fileService
    ) {}

    // -----------------------------------------------------------------------
    // Publicación
    // -----------------------------------------------------------------------

    /**
     * Publicar el informe de acreditación de un ciclo.
     *
     * Crea el registro INFORME_ACREDITACION, marca el archivo PDF como
     * públicamente accesible mediante token (HU-023) con vigencia igual
     * a la fecha de vencimiento de la acreditación, y devuelve el modelo
     * hidratado con sus relaciones.
     *
     * @param  AccreditationCycle $cycle     Ciclo al que pertenece el informe.
     * @param  array              $data      Campos: archivo_id, numero_resolucion, fecha_resolucion,
     *                                       vigencia_desde, vigencia_hasta, observaciones (opt).
     * @param  User               $publisher Usuario que realiza la publicación.
     * @return AccreditationReport           Informe recién publicado con relaciones.
     *
     * @throws \InvalidArgumentException  Si el ciclo ya tiene un informe registrado.
     */
    public function publishReport(
        AccreditationCycle $cycle,
        array $data,
        User $publisher
    ): AccreditationReport {
        return DB::transaction(function () use ($cycle, $data, $publisher) {
            $file = File::findOrFail($data['archivo_id']);
            // Un ciclo solo puede tener un informe (publicado o despublicado).
            // Si ya existe, se debe despublicar primero o corregir los datos antes
            // de volver a publicar mediante republishReport().
            if ($cycle->accreditationReport()->exists()) {
                throw new \InvalidArgumentException(
                    'Este ciclo ya tiene un informe de acreditación registrado. ' .
                    'Despublíquelo primero si necesita corregir los datos.'
                );
            }

            // Crear el registro del informe en estado publicado
            $report = AccreditationReport::create([
                'ciclo_acreditacion_id'  => $cycle->ciclo_acreditacion_id,
                'archivo_id'             => $file->archivo_id,
                'usuario_publicacion_id' => $publisher->usuario_id,
                'estado'                 => AccreditationReport::STATUS_PUBLISHED,
                'numero_resolucion'      => $data['numero_resolucion'],
                'fecha_resolucion'       => $data['fecha_resolucion'],
                'vigencia_desde'         => $data['vigencia_desde'],
                'vigencia_hasta'         => $data['vigencia_hasta'],
                'fecha_publicacion'      => now(),
                'observaciones'          => $data['observaciones'] ?? null,
            ]);

            // Hacer el PDF públicamente accesible vía token HU-023.
            // La vigencia del enlace público coincide con el fin de la acreditación.
            $vigenciaHasta = new \DateTime($data['vigencia_hasta'] . ' 23:59:59');
            $this->fileService->makePublic($file, $vigenciaHasta);

            Log::info('Informe de acreditación publicado', [
                'informe_id'        => $report->informe_acreditacion_id,
                'ciclo_id'          => $cycle->ciclo_acreditacion_id,
                'numero_resolucion' => $report->numero_resolucion,
                'publicado_por'     => $publisher->usuario_id,
            ]);

            return $report->load(['accreditationCycle', 'file', 'publishedBy']);
        });
    }

    /**
     * Despublicar un informe de acreditación.
     *
     * Cambia el estado a 'despublicado' y revoca el token de acceso público
     * del archivo PDF. El registro se conserva en BD para trazabilidad.
     * Si se indica un motivo, se adjunta al campo observaciones.
     *
     * @param  AccreditationReport $report Informe a despublicar.
     * @param  string|null         $reason Motivo de la despublicación (opcional).
     * @return AccreditationReport         Informe actualizado con relaciones.
     *
     * @throws \InvalidArgumentException  Si el informe ya está despublicado.
     */
    public function unpublishReport(AccreditationReport $report, ?string $reason = null): AccreditationReport
    {
        return DB::transaction(function () use ($report, $reason) {
            if ($report->isUnpublished()) {
                throw new \InvalidArgumentException('El informe ya se encuentra despublicado.');
            }

            // Revocar el acceso público al archivo PDF
            if ($report->file) {
                $this->fileService->revokePublicAccess($report->file);
            }

            // Adjuntar el motivo al campo de observaciones si se proporciona
            $observaciones = $report->observaciones;
            if ($reason) {
                $observaciones = $observaciones
                    ? $observaciones . "\n[Despublicado]: " . $reason
                    : '[Despublicado]: ' . $reason;
            }

            $report->update([
                'estado'        => AccreditationReport::STATUS_UNPUBLISHED,
                'observaciones' => $observaciones,
            ]);

            Log::info('Informe de acreditación despublicado', [
                'informe_id' => $report->informe_acreditacion_id,
                'motivo'     => $reason,
            ]);

            return $report->fresh(['accreditationCycle', 'file', 'publishedBy']);
        });
    }

    // -----------------------------------------------------------------------
    // Consultas
    // -----------------------------------------------------------------------

    /**
     * Listar los informes publicados actualmente (acceso público sin autenticación).
     *
     * Usado por el endpoint público /api/accreditation-reports
     * que no requiere Sanctum token (HU-028 acceso libre).
     *
     * @param  array $filters  Claves opcionales: carrera_id, sede_id, per_page.
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getPublicReports(array $filters = [])
    {
        $perPage = $filters['per_page'] ?? 15;

        $query = AccreditationReport::published()
            ->with(['accreditationCycle.careerCampus.career', 'file'])
            ->latest('fecha_publicacion');

        if (!empty($filters['carrera_id'])) {
            $query->whereHas(
                'accreditationCycle.careerCampus',
                fn($q) => $q->where('carrera_id', $filters['carrera_id'])
            );
        }

        if (!empty($filters['sede_id'])) {
            $query->whereHas(
                'accreditationCycle.careerCampus',
                fn($q) => $q->where('sede_id', $filters['sede_id'])
            );
        }

        return $query->paginate($perPage);
    }

    /**
     * Obtener el informe asociado a un ciclo concreto.
     * Devuelve null si el ciclo aún no tiene informe registrado.
     *
     * @param  AccreditationCycle $cycle
     * @return AccreditationReport|null
     */
    public function getReportByCycle(AccreditationCycle $cycle): ?AccreditationReport
    {
        return $cycle->accreditationReport()
            ->with(['file', 'publishedBy'])
            ->first();
    }

    /**
     * Buscar un informe por su ID primario.
     *
     * @param  int                  $id
     * @return AccreditationReport|null
     */
    public function findById(int $id): ?AccreditationReport
    {
        return AccreditationReport::with(['accreditationCycle', 'file', 'publishedBy'])
            ->find($id);
    }
}
