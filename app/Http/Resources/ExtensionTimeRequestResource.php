<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

/**
 * Resource para formatear solicitudes de ampliación del profesor (RF-15).
 * Incluye solo información esencial que el profesor necesita ver.
 * 
 * @phpstan-ignore-next-line
 */
class ExtensionTimeRequestResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'solicitud_ampliacion_id' => $this->solicitud_ampliacion_id,
            'usuario_id' => $this->usuario_id,
            'usuario' => $this->whenLoaded('user', [
                'usuario_id' => $this->user?->usuario_id,
                'nombre'     => $this->user?->nombre,
                'cedula'     => $this->user?->cedula,
                'email'      => $this->user?->email,
            ]),

            // Soporta modelo tradicional (evidencia) y modelo flexible (elemento)
            // Solo incluye el campo si tiene valor (whenNotNull omite el key si es null)
            'evidencia_asignacion_id' => $this->whenNotNull($this->evidencia_asignacion_id),
            'elemento_asignacion_id'  => $this->whenNotNull($this->elemento_asignacion_id),

            'evidencia_asignacion' => $this->when(
                $this->evidencia_asignacion_id !== null,
                fn () => $this->whenLoaded('evidenceAssignment', function () {
                    $ea = $this->evidenceAssignment;
                    return $ea ? [
                        'evidencia_asignacion_id' => $ea->evidencia_asignacion_id,
                        'estado'                  => $ea->estado,
                        'fecha_limite'            => $ea->fecha_limite?->format('Y-m-d'),
                        'evidence'                => $ea->evidence ? [
                            'evidencia_id' => $ea->evidence->evidencia_id,
                            'nombre'       => $ea->evidence->nombre,
                            'criterion'    => $ea->evidence->criterion ? [
                                'criterio_id' => $ea->evidence->criterion->criterio_id,
                                'nombre'      => $ea->evidence->criterion->nombre,
                            ] : null,
                        ] : null,
                    ] : null;
                })
            ),

            'elemento_asignacion' => $this->when(
                $this->elemento_asignacion_id !== null,
                fn () => $this->whenLoaded('elementAssignment', function () {
                    $ea = $this->elementAssignment;
                    return $ea ? [
                        'elemento_asignacion_id' => $ea->elemento_asignacion_id,
                        'estado'                 => $ea->estado,
                        'fecha_limite'           => $ea->fecha_limite?->format('Y-m-d'),
                        'element'                => $ea->element ? [
                            'elemento_id'  => $ea->element->elemento_id,
                            'nomenclatura' => $ea->element->nomenclatura,
                            'descripcion'  => $ea->element->descripcion,
                        ] : null,
                    ] : null;
                })
            ),

            // Estado de la solicitud
            'estado'         => $this->estado,
            'motivo'         => $this->motivo,

            // Fechas clave
            'fecha_solicitud' => $this->created_at?->format('Y-m-d H:i:s'),
            'fecha_sugerida'  => $this->fecha_sugerida?->format('Y-m-d'),

            // Campos del encargado — solo aparecen cuando la solicitud fue resuelta
            'fecha_resolucion' => $this->whenNotNull($this->fecha_resolucion?->format('Y-m-d H:i:s')),
            'justificacion'    => $this->whenNotNull($this->justificacion),

            // Timestamps
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
