<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource para serializar el informe de acreditación.
 *
 * HU-027: Publicación de informe de acreditación aprobado.
 */
class AccreditationReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            // Identificador y estado
            'informe_acreditacion_id' => $this->informe_acreditacion_id,
            'estado'                  => $this->estado,
            'is_vigente'              => $this->isCurrentlyValid(),
            'esta_acreditada'         => (bool) $this->esta_acreditada,

            // Datos de la resolución SINAES
            'numero_resolucion' => $this->numero_resolucion,
            'fecha_resolucion'  => $this->fecha_resolucion?->format('Y-m-d'),
            'vigencia_desde'    => $this->vigencia_desde?->format('Y-m-d'),
            'vigencia_hasta'    => $this->vigencia_hasta?->format('Y-m-d'),

            // Metadatos de publicación
            'fecha_publicacion' => optional($this->fecha_publicacion)->toISOString(),
            'observaciones'     => $this->observaciones,

            // Timestamps
            'created_at' => optional($this->created_at)->toISOString(),
            'updated_at' => optional($this->updated_at)->toISOString(),

            // Ciclo de acreditación (cuando está cargado)
            'ciclo' => $this->whenLoaded('accreditationCycle', fn() => [
                'ciclo_acreditacion_id' => $this->accreditationCycle?->ciclo_acreditacion_id,
                'nombre'                => $this->accreditationCycle?->nombre,
                'estado'                => $this->accreditationCycle?->estado,
                // Carrera y sede (anidados si están cargados en el ciclo)
                'carrera_sede' => $this->when(
                    $this->accreditationCycle?->relationLoaded('careerCampus'),
                    fn() => [
                        'carrera_sede_id' => $this->accreditationCycle->careerCampus?->carrera_sede_id,
                        'carrera_nombre'  => $this->accreditationCycle->careerCampus?->career?->nombre,
                        'sede_nombre'     => $this->accreditationCycle->careerCampus?->campus?->nombre,
                    ]
                ),
            ]),

            // Archivo PDF adjunto (cuando está cargado)
            'archivo' => $this->whenLoaded('file', fn() => [
                'archivo_id'      => $this->file?->archivo_id,
                'nombre_original' => $this->file?->nombre_original,
                'tipo_mime'       => $this->file?->tipo_mime,
                'tamanio'         => $this->file?->tamanio,
                'is_publico'      => $this->file?->is_publico,
                'url_publica'     => $this->when(
                    $this->file?->isPubliclyAccessible(),
                    fn() => $this->file->getPublicUrl()
                ),
            ]),

            // Usuario que publicó (cuando está cargado)
            'publicado_por' => $this->whenLoaded('publishedBy', fn() => [
                'usuario_id' => $this->publishedBy?->usuario_id,
                'nombre'     => $this->publishedBy?->nombre,
            ]),
        ];
    }
}
