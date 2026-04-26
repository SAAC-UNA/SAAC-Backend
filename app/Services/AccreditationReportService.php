<?php

namespace App\Services;

use App\Models\AccreditationCycle;
use App\Models\AccreditationReport;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Servicio de Informes de Acreditación
 *
 * HU-027: Publicación de informe de acreditación aprobado.
 *
 * Responsabilidades:
 * - Publicar la resolución oficial de SINAES en un ciclo de acreditación.
 * - Despublicar el informe revocando el acceso público al archivo PDF.
 * - Consultar informes publicados (sin autenticación, acceso público).
 * - Resolver el informe asociado a un ciclo concreto.
 *
 * El acceso público al archivo reutiliza el sistema de tokens de HU-023:
 * makePublic() → token UUID → ruta /p/{token} sin autenticación.
 */
class AccreditationReportService
{
    public function __construct() {}

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
     * @param  AccreditationCycle  $cycle  Ciclo al que pertenece el informe.
     * @param  array  $data  Campos: archivo_id, numero_resolucion, fecha_resolucion,
     *                       vigencia_desde, vigencia_hasta, observaciones (opt).
     * @param  User  $publisher  Usuario que realiza la publicación.
     * @return AccreditationReport Informe recién publicado con relaciones.
     *
     * @throws \InvalidArgumentException Si el ciclo ya tiene un informe registrado.
     */
    public function publishReport(
        AccreditationCycle $cycle,
        array $data,
        User $publisher
    ): AccreditationReport {
        return DB::transaction(function () use ($data, $publisher) {
            // El archivo llega como UploadedFile desde el request
            /** @var UploadedFile $uploadedFile */
            $uploadedFile = $data['archivo'];

            $disk = config('saac.storage_disk', 'simulated_nas');
            $extension = $uploadedFile->getClientOriginalExtension();
            $uuid = (string) Str::uuid();
            $filename = "{$uuid}.{$extension}";
            $path = Storage::disk($disk)->putFileAs('', $uploadedFile, $filename);


            // Crear el registro del informe en estado publicado
            $report = AccreditationReport::create([
                'proceso_id' => $data['proceso_id'],
                'usuario_id' => $publisher->usuario_id,
                'usuario_publicacion_id' => $publisher->usuario_id,
                'estado' => AccreditationReport::STATUS_PUBLISHED,
                'fecha_publicacion' => now(),
                'observaciones' => $data['observaciones'] ?? null,
                'fecha_subida' => now(),
                'tipo' => 'archivo',
                'path' => $path,
                'nombre_original' => $uploadedFile->getClientOriginalName(),
                'tamanio' => $uploadedFile->getSize(),
                'tipo_mime' => $uploadedFile->getMimeType(),
                'is_publico' => true,
                'token_publico' => (string) Str::uuid(),
                'link_expira_en' => now()->addYear(),
            ]);

            Log::info('Informe de acreditación publicado', [
                'informe_id' => $report->informe_archivo_id,
                'publicado_por' => $publisher->usuario_id,
            ]);

            return $report->load(['publishedBy']);
        });
    }

    /**
     * Notifica a los usuarios de la carrera cuando se publica un informe.
     */
    public function notifyPublication(AccreditationCycle $cycle, AccreditationReport $report, User $publisher): void
    {
        try {
            $carreraSede = $cycle->carrera_sede_id;
            $recipientIds = User::whereHas('careers', fn ($careerQuery) => $careerQuery->where('CARRERA_SEDE.carrera_sede_id', $carreraSede))
                ->where('status', User::STATUS_ACTIVE)
                ->where('usuario_id', '!=', $publisher->usuario_id)
                ->pluck('usuario_id')
                ->toArray();

            if (! empty($recipientIds)) {
                NotificationService::createMany($recipientIds, [
                    'tipo_evento' => Notification::TIPO_PUBLICACION_INFORME,
                    'titulo' => 'Nuevo informe de acreditación publicado',
                    'mensaje' => "Se ha publicado el informe de acreditación del ciclo \"{$cycle->nombre}\".",
                    'enlace' => '/informes-acreditacion',
                    'relacionado' => $report,
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('Error al enviar notificaciones de publicación de informe', [
                'informe_id' => $report->informe_archivo_id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Notifica a los usuarios de la carrera cuando se despublica un informe.
     */
    public function notifyUnpublication(AccreditationReport $report, User $actor): void
    {
        try {
            $report->loadMissing('process.cycle');
            $carreraSede = $report->process?->cycle?->carrera_sede_id;

            if (! $carreraSede) {
                return;
            }

            $recipientIds = User::whereHas('careers', fn ($careerQuery) => $careerQuery->where('CARRERA_SEDE.carrera_sede_id', $carreraSede))
                ->where('status', User::STATUS_ACTIVE)
                ->where('usuario_id', '!=', $actor->usuario_id)
                ->pluck('usuario_id')
                ->toArray();

            if (! empty($recipientIds)) {
                NotificationService::createMany($recipientIds, [
                    'tipo_evento' => Notification::TIPO_DESPUBLICACION_INFORME,
                    'titulo' => 'Informe de acreditación despublicado',
                    'mensaje' => "El informe de acreditación del ciclo \"{$report->process?->cycle?->nombre}\" ha sido despublicado.",
                    'enlace' => '/informes-acreditacion',
                    'relacionado' => $report,
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('Error al enviar notificaciones de despublicación de informe', [
                'informe_id' => $report->informe_archivo_id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Despublicar un informe de acreditación.
     *
     * Cambia el estado a 'despublicado' y revoca el token de acceso público
     * del archivo PDF. El registro se conserva en BD para trazabilidad.
     * Si se indica un motivo, se adjunta al campo observaciones.
     *
     * @param  AccreditationReport  $report  Informe a despublicar.
     * @param  string|null  $reason  Motivo de la despublicación (opcional).
     * @return AccreditationReport Informe actualizado con relaciones.
     *
     * @throws \InvalidArgumentException Si el informe ya está despublicado.
     */
    public function unpublishReport(AccreditationReport $report, ?string $reason = null): AccreditationReport
    {
        return DB::transaction(function () use ($report, $reason) {
            if ($report->isUnpublished()) {
                throw new \InvalidArgumentException('El informe ya se encuentra despublicado.');
            }

            // Revocar el acceso público
            $report->update([
                'is_publico' => false,
                'token_publico' => null,
                'link_expira_en' => null,
            ]);

            // Adjuntar el motivo al campo de observaciones si se proporciona
            $observaciones = $report->observaciones;
            if ($reason) {
                $observaciones = $observaciones
                    ? $observaciones."\n[Despublicado]: ".$reason
                    : '[Despublicado]: '.$reason;
            }

            $report->update([
                'estado' => AccreditationReport::STATUS_UNPUBLISHED,
                'observaciones' => $observaciones,
            ]);

            Log::info('Informe de acreditación despublicado', [
                'informe_id' => $report->informe_archivo_id,
                'motivo' => $reason,
            ]);

            return $report->fresh(['process.cycle', 'publishedBy']);
        });
    }

    /**
     * Editar los datos de un informe de acreditación (publicado o despublicado).
     *
     * Permite corregir campos como observaciones o reemplazar el archivo PDF.
     * Si se cambia el archivo, se revoca el token del archivo anterior y se
     * genera uno nuevo para el archivo nuevo (si el informe está publicado).
     *
     * @param  AccreditationReport  $report  Informe a editar.
     * @param  array  $data  Campos a actualizar (todos opcionales).
     * @return AccreditationReport Informe actualizado con relaciones.
     */
    public function updateReport(AccreditationReport $report, array $data, User $editor): AccreditationReport
    {
        return DB::transaction(function () use ($report, $data) {
            $archivoChanged = isset($data['archivo']); // UploadedFile presente

            if ($archivoChanged) {
                // El archivo llega como UploadedFile desde el request
                /** @var UploadedFile $uploadedFile */
                $uploadedFile = $data['archivo'];

                $disk = config('saac.storage_disk', 'simulated_nas');
                $extension = $uploadedFile->getClientOriginalExtension();
                $uuid = (string) Str::uuid();
                $filename = "{$uuid}.{$extension}";
                $path = Storage::disk($disk)->putFileAs('', $uploadedFile, $filename);

                $data['path'] = $path;
                $data['nombre_original'] = $uploadedFile->getClientOriginalName();
                $data['tamanio'] = $uploadedFile->getSize();
                $data['tipo_mime'] = $uploadedFile->getMimeType();

                // Si el informe está publicado, generar nuevo token
                if ($report->isPublished()) {
                    $data['is_publico'] = true;
                    $data['token_publico'] = (string) Str::uuid();
                    $data['link_expira_en'] = now()->addYear();
                }
            }

            // Eliminar 'archivo' del array antes de actualizar el modelo
            unset($data['archivo']);

            $report->update(array_filter($data, fn ($v) => $v !== null || array_key_exists('observaciones', $data)));

            Log::info('Informe de acreditación editado', [
                'informe_id' => $report->informe_archivo_id,
                'campos_modificados' => array_keys($data),
            ]);

            return $report->fresh(['process.cycle.careerCampus.career', 'process.cycle.careerCampus.campus', 'publishedBy']);
        });
    }

    /**
     * Eliminar físicamente un informe de acreditación.
     *
     * Revoca el acceso público al PDF antes de eliminar el registro.
     * Solo debe usarse para corregir errores de publicación (Superusuario).
     * Libera la constraint UNIQUE del ciclo, permitiendo volver a publicar.
     *
     * @param  AccreditationReport  $report  Informe a eliminar.
     */
    public function deleteReport(AccreditationReport $report): void
    {
        DB::transaction(function () use ($report) {
            // Eliminar archivo físico
            $disk = config('saac.storage_disk', 'simulated_nas');
            if ($report->path && Storage::disk($disk)->exists($report->path)) {
                Storage::disk($disk)->delete($report->path);
            }

            Log::info('Informe de acreditación eliminado', [
                'informe_id' => $report->informe_archivo_id,
                'proceso_id' => $report->proceso_id,
            ]);

            $report->delete();
        });
    }

    // -----------------------------------------------------------------------
    // Consultas
    // -----------------------------------------------------------------------

    /**
     * Listar los informes publicados actualmente (acceso público sin autenticación).
     *
     * Usado por el endpoint público /api/accreditation-reports
     * que no requiere Sanctum token (HU-027 acceso libre).
     *
     * @param  array  $filters  Claves opcionales: carrera_id, sede_id, per_page.
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getPublicReports(array $filters = [])
    {
        $perPage = $filters['per_page'] ?? 15;

        $query = AccreditationReport::published()
            ->with(['process.cycle.careerCampus.career'])
            ->latest('fecha_publicacion');

        if (! empty($filters['carrera_id'])) {
            $query->whereHas(
                'process.cycle.careerCampus',
                fn ($consultaCarrera) => $consultaCarrera->where('carrera_id', $filters['carrera_id'])
            );
        }

        if (! empty($filters['sede_id'])) {
            $query->whereHas(
                'process.cycle.careerCampus',
                fn ($consultaSede) => $consultaSede->where('sede_id', $filters['sede_id'])
            );
        }

        if (! empty($filters['carrera_campus_id'])) {
            $query->whereHas(
                'process.cycle.careerCampus',
                fn ($q) => $q->where('carrera_sede_id', $filters['carrera_campus_id'])
            );
        }

        return $query->paginate($perPage);
    }

    /**
     * Listar informes para panel administrativo.
     *
     * A diferencia del listado público, este endpoint puede incluir informes
     * despublicados cuando include_unpublished = true.
     *
     * @param  array  $filters  Claves opcionales: carrera_id, sede_id,
     *                          carrera_campus_id, per_page, include_unpublished.
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getAdminReports(array $filters = [])
    {
        $perPage = $filters['per_page'] ?? 15;
        $includeUnpublished = (bool) ($filters['include_unpublished'] ?? false);

        $query = AccreditationReport::query()
            ->with(['accreditationCycle.careerCampus.career', 'file', 'publishedBy'])
            ->latest('fecha_publicacion');

        if (! $includeUnpublished) {
            $query->where('estado', AccreditationReport::STATUS_PUBLISHED);
        }

        if (! empty($filters['carrera_id'])) {
            $query->whereHas(
                'accreditationCycle.careerCampus',
                fn ($consultaCarrera) => $consultaCarrera->where('carrera_id', $filters['carrera_id'])
            );
        }

        if (! empty($filters['sede_id'])) {
            $query->whereHas(
                'accreditationCycle.careerCampus',
                fn ($consultaSede) => $consultaSede->where('sede_id', $filters['sede_id'])
            );
        }

        if (! empty($filters['carrera_campus_id'])) {
            $query->whereHas(
                'accreditationCycle.careerCampus',
                fn ($q) => $q->where('carrera_sede_id', $filters['carrera_campus_id'])
            );
        }

        return $query->paginate($perPage);
    }

    /**
     * Obtener el informe asociado a un ciclo concreto.
     * Devuelve null si el ciclo aún no tiene informe registrado.
     */
    public function getReportByCycle(AccreditationCycle $cycle): ?AccreditationReport
    {
        return AccreditationReport::whereHas('process', fn ($q) => $q->where('ciclo_acreditacion_id', $cycle->ciclo_acreditacion_id))
            ->with(['publishedBy'])
            ->first();
    }

    /**
     * Buscar un informe por su ID primario.
     */
    public function findById(int $id): ?AccreditationReport
    {
        return AccreditationReport::with(['process.cycle', 'publishedBy'])
            ->find($id);
    }
}
