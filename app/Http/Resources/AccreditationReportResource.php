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
            'informe_archivo_id' => $this->informe_archivo_id,
            'estado'                  => $this->estado,

            // Metadatos de publicación
            'fecha_publicacion' => optional($this->fecha_publicacion)->toISOString(),
            'observaciones'     => $this->observaciones,

            // Timestamps
            'created_at' => optional($this->created_at)->toISOString(),
            'updated_at' => optional($this->updated_at)->toISOString(),

            // Proceso (cuando está cargado)
            'proceso' => $this->whenLoaded('process', fn() => [
                'proceso_id' => $this->process?->proceso_id,
                'nombre'     => $this->process?->nombre,
            ]),

            // Información del archivo
            'archivo' => [
                'nombre_original' => $this->nombre_original,
                'tipo_mime'       => $this->tipo_mime,
                'tamanio'         => $this->tamanio,
                'is_publico'      => $this->is_publico,
                'url_publica'     => $this->getPublicUrl(),
            ],

            // Usuario que publicó (cuando está cargado)
            'publicado_por' => $this->whenLoaded('publishedBy', fn() => [
                'usuario_id' => $this->publishedBy?->usuario_id,
                'nombre'     => $this->publishedBy?->nombre,
            ]),
        ];
    }
}
