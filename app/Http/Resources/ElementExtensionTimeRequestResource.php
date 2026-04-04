<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource para solicitudes de ampliación de plazo — modelo flexible (elemento).
 * Opera sobre SOLICITUD_AMPLIACION_ELEMENTO.
 */
class ElementExtensionTimeRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'solicitud_ampliacion_elemento_id' => $this->solicitud_ampliacion_elemento_id,
            'usuario_id'                       => $this->usuario_id,
            'usuario' => $this->whenLoaded('user', [
                'usuario_id' => $this->user?->usuario_id,
                'nombre'     => $this->user?->nombre,
                'cedula'     => $this->user?->cedula,
                'email'      => $this->user?->email,
            ]),

            'elemento_asignacion_id' => $this->elemento_asignacion_id,
            'elemento_asignacion'    => $this->whenLoaded('elementAssignment', function () {
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
            }),

            'estado'           => $this->estado,
            'motivo'           => $this->motivo,
            'fecha_solicitud'  => $this->created_at?->format('Y-m-d H:i:s'),
            'fecha_sugerida'   => $this->fecha_sugerida?->format('Y-m-d'),

            'fecha_resolucion' => $this->whenNotNull($this->fecha_resolucion?->format('Y-m-d H:i:s')),
            'justificacion'    => $this->whenNotNull($this->justificacion),

            'resolutor' => $this->whenLoaded('resolutor', function () {
                return $this->resolutor ? [
                    'usuario_id' => $this->resolutor->usuario_id,
                    'nombre'     => $this->resolutor->nombre,
                ] : null;
            }),

            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
