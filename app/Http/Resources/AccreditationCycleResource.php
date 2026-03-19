<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AccreditationCycleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Devuelve los datos del ciclo de acreditación, incluyendo relaciones cuando están cargadas
        return [
            // Datos principales del ciclo
            'ciclo_acreditacion_id' => $this->ciclo_acreditacion_id,
            'carrera_sede_id'       => $this->carrera_sede_id,
            'nombre'                => $this->nombre,
            'estado'                => $this->estado,
            'created_at'            => optional($this->created_at)->toISOString(),
            'updated_at'            => optional($this->updated_at)->toISOString(),

            // Relación con carrera sede (cuando está cargada)
            'carrera_sede' => $this->whenLoaded('careerCampus', fn() => [
                'carrera_sede_id' => $this->careerCampus?->carrera_sede_id,
                'sede_id'         => $this->careerCampus?->sede_id,
                'carrera_id'      => $this->careerCampus?->carrera_id,
            ]),

            // Relación con procesos (cuando está cargada)
            'processes' => $this->whenLoaded('processes', fn() =>
                $this->processes->map(fn($p) => [
                    'proceso_id'   => $p->proceso_id,
                    'tipo_proceso' => $p->tipo_proceso,
                ])
            ),
        ];
    }
}