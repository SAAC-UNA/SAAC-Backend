<?php

namespace App\Services;

use App\Models\AccreditationCycle;
use App\Models\AccreditationReport;
use App\Models\File;
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
    // Almacenamiento de archivos de resolución
    // -----------------------------------------------------------------------

    /**
     * Almacena el PDF de la resolución SINAES en disco y crea el registro ARCHIVO.
     * No asocia el archivo a ninguna evidencia ni proceso (son resoluciones institucionales).
     */
    private function storeReportFile(UploadedFile $uploadedFile, User $publisher): File
    {
        $disk      = config('saac.storage_disk', 'simulated_nas');
        $extension = $uploadedFile->getClientOriginalExtension();
        $uuid      = (string) Str::uuid();
        $filename  = "{$uuid}.{$extension}";
        $path      = Storage::disk($disk)->putFileAs('', $uploadedFile, $filename);

        return File::create([
            'evidencia_id'    => null,
            'elemento_id'     => null,
            'proceso_id'      => null,
            'usuario_id'      => $publisher->usuario_id,
            'fecha_subida'    => now(),
            'tipo'            => 'archivo',
            'path'            => $path,
            'url'             => null,
            'nombre_original' => $uploadedFile->getClientOriginalName(),
            'tamanio'         => $uploadedFile->getSize(),
            'tipo_mime'       => $uploadedFile->getMimeType(),
            'is_publico'      => false,
        ]);
    }

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
            // El archivo llega como UploadedFile desde el request
            /** @var UploadedFile $uploadedFile */
            $uploadedFile = $data['archivo'];
            $file = $this->storeReportFile($uploadedFile, $publisher);

            // Un ciclo solo puede tener un informe (publicado o despublicado).
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
                'fecha_resolucion'       => now()->toDateString(),
                'vigencia_desde'         => $data['vigencia_desde'],
                'vigencia_hasta'         => $data['vigencia_hasta'],
                'fecha_publicacion'      => now(),
                'observaciones'          => $data['observaciones'] ?? null,
                'esta_acreditada'        => $data['esta_acreditada'],
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
     * Notifica a los usuarios de la carrera cuando se publica un informe.
     */
    public function notifyPublication(AccreditationCycle $cycle, AccreditationReport $report, User $publisher): void
    {
        try {
            $carreraId    = $cycle->loadMissing('careerCampus')->careerCampus->carrera_id;
            $recipientIds = User::whereHas('careers', fn($careerQuery) => $careerQuery->where('CARRERA.carrera_id', $carreraId))
                ->where('status', User::STATUS_ACTIVE)
                ->where('usuario_id', '!=', $publisher->usuario_id)
                ->pluck('usuario_id')
                ->toArray();

            if (!empty($recipientIds)) {
                NotificationService::createMany($recipientIds, [
                    'tipo_evento' => Notification::TIPO_PUBLICACION_INFORME,
                    'titulo'      => 'Nuevo informe de acreditación publicado',
                    'mensaje'     => "Se ha publicado el informe de acreditación del ciclo \"{$cycle->nombre}\" (resolución: {$report->numero_resolucion}).",
                    'enlace'      => '/informes-acreditacion',
                    'relacionado' => $report,
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('Error al enviar notificaciones de publicación de informe', [
                'informe_id' => $report->informe_acreditacion_id,
                'error'      => $e->getMessage(),
            ]);
        }
    }

    /**
     * Notifica a los usuarios de la carrera cuando se despublica un informe.
     */
    public function notifyUnpublication(AccreditationReport $report, User $actor): void
    {
        try {
            $carreraId    = $report->accreditationCycle->loadMissing('careerCampus')->careerCampus->carrera_id;
            $recipientIds = User::whereHas('careers', fn($careerQuery) => $careerQuery->where('CARRERA.carrera_id', $carreraId))
                ->where('status', User::STATUS_ACTIVE)
                ->where('usuario_id', '!=', $actor->usuario_id)
                ->pluck('usuario_id')
                ->toArray();

            if (!empty($recipientIds)) {
                NotificationService::createMany($recipientIds, [
                    'tipo_evento' => Notification::TIPO_DESPUBLICACION_INFORME,
                    'titulo'      => 'Informe de acreditación despublicado',
                    'mensaje'     => "El informe de acreditación del ciclo \"{$report->accreditationCycle->nombre}\" (resolución: {$report->numero_resolucion}) ha sido despublicado.",
                    'enlace'      => '/informes-acreditacion',
                    'relacionado' => $report,
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('Error al enviar notificaciones de despublicación de informe', [
                'informe_id' => $report->informe_acreditacion_id,
                'error'      => $e->getMessage(),
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

    /**
     * Editar los datos de un informe de acreditación (publicado o despublicado).
     *
     * Permite corregir campos como numero_resolucion, fechas o archivo PDF.
     * Si se cambia el archivo, se revoca el token del archivo anterior y se
     * genera uno nuevo para el archivo nuevo (si el informe está publicado).
     *
     * @param  AccreditationReport $report Informe a editar.
     * @param  array               $data   Campos a actualizar (todos opcionales).
     * @return AccreditationReport         Informe actualizado con relaciones.
     */
    public function updateReport(AccreditationReport $report, array $data, User $editor): AccreditationReport
    {
        return DB::transaction(function () use ($report, $data, $editor) {
            $archivoChanged = isset($data['archivo']); // UploadedFile presente

            if ($archivoChanged) {
                // Revocar el token público del archivo anterior
                if ($report->file) {
                    $this->fileService->revokePublicAccess($report->file);
                }

                // Almacenar el nuevo archivo y registrarlo en ARCHIVO
                $newFile = $this->storeReportFile($data['archivo'], $editor);
                $data['archivo_id'] = $newFile->archivo_id;

                // Si el informe está publicado, hacer público el nuevo archivo
                if ($report->isPublished()) {
                    $vigenciaHasta = new \DateTime($data['vigencia_hasta'] ?? $report->vigencia_hasta->format('Y-m-d') . ' 23:59:59');
                    $this->fileService->makePublic($newFile, $vigenciaHasta);
                }
            }

            // Eliminar 'archivo' del array antes de actualizar el modelo
            unset($data['archivo']);

            $report->update(array_filter($data, fn($v) => $v !== null || array_key_exists('observaciones', $data)));

            Log::info('Informe de acreditación editado', [
                'informe_id'        => $report->informe_acreditacion_id,
                'campos_modificados' => array_keys($data),
            ]);

            return $report->fresh(['accreditationCycle.careerCampus.career', 'accreditationCycle.careerCampus.campus', 'file', 'publishedBy']);
        });
    }

    /**
     * Eliminar físicamente un informe de acreditación.
     *
     * Revoca el acceso público al PDF antes de eliminar el registro.
     * Solo debe usarse para corregir errores de publicación (Superusuario).
     * Libera la constraint UNIQUE del ciclo, permitiendo volver a publicar.
     *
     * @param  AccreditationReport $report Informe a eliminar.
     * @return void
     */
    public function deleteReport(AccreditationReport $report): void
    {
        DB::transaction(function () use ($report) {
            // Revocar token público del PDF si existe
            if ($report->file) {
                $this->fileService->revokePublicAccess($report->file);
            }

            Log::info('Informe de acreditación eliminado', [
                'informe_id'        => $report->informe_acreditacion_id,
                'numero_resolucion' => $report->numero_resolucion,
                'ciclo_id'          => $report->ciclo_acreditacion_id,
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
                fn($consultaCarrera) => $consultaCarrera->where('carrera_id', $filters['carrera_id'])
            );
        }

        if (!empty($filters['sede_id'])) {
            $query->whereHas(
                'accreditationCycle.careerCampus',
                fn($consultaSede) => $consultaSede->where('sede_id', $filters['sede_id'])
            );
        }

        if (!empty($filters['carrera_campus_id'])) {
            $query->whereHas(
                'accreditationCycle.careerCampus',
                fn($q) => $q->where('carrera_sede_id', $filters['carrera_campus_id'])
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

    // -----------------------------------------------------------------------
    // Helpers privados
    // -----------------------------------------------------------------------

    /**
     * Almacena el PDF de un informe de acreditación en el disco configurado
     * y crea el registro correspondiente en la tabla ARCHIVO.
     *
     * A diferencia de las evidencias, un informe no pertenece a ningún elemento
     * ni proceso de autoevaluación, por lo que esos campos se almacenan como null.
     *
     * @param  UploadedFile $uploadedFile  Archivo subido desde la petición HTTP.
     * @param  User         $uploader      Usuario que realiza la subida.
     * @return File                        Registro ARCHIVO recién creado.
     */
    private function storeReportFile(UploadedFile $uploadedFile, User $uploader): File
    {
        $disk      = config('saac.storage_disk', 'simulated_nas');
        $extension = $uploadedFile->getClientOriginalExtension();
        $uuid      = (string) Str::uuid();
        $filename  = "{$uuid}.{$extension}";

        $path = Storage::disk($disk)->putFileAs('', $uploadedFile, $filename);

        return File::create([
            'evidencia_id'    => null,
            'elemento_id'     => null,
            'usuario_id'      => $uploader->usuario_id,
            'proceso_id'      => null,
            'fecha_subida'    => now(),
            'tipo'            => 'archivo',
            'path'            => $path,
            'url'             => null,
            'nombre_original' => $uploadedFile->getClientOriginalName(),
            'tamanio'         => $uploadedFile->getSize(),
            'tipo_mime'       => $uploadedFile->getMimeType(),
            'is_publico'      => false,
        ]);
    }
}
