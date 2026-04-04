<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource para formatear la salida JSON de ExtensionRequest.
 * 
 * Transforma el modelo a un formato JSON consistente para la API.
 * Incluye relaciones anidadas cuando están cargadas (evidenceAssignment, user, resolutor).
 */
class ExtensionRequestResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            // Datos principales de la solicitud
            'solicitud_ampliacion_id' => $this->solicitud_ampliacion_id,
            'evidencia_asignacion_id' => $this->when($this->evidencia_asignacion_id !== null, $this->evidencia_asignacion_id),
            'elemento_asignacion_id'  => $this->when($this->elemento_asignacion_id !== null, $this->elemento_asignacion_id),
            'usuario_id' => $this->usuario_id,
            'motivo' => $this->motivo,
            'fecha_sugerida' => optional($this->fecha_sugerida)->toISOString(),
            'estado' => $this->estado,
            'fecha_resolucion' => optional($this->fecha_resolucion)->toISOString(),
            'usuario_resolutor_id' => $this->usuario_resolutor_id,
            'justificacion' => $this->justificacion,
            'created_at' => optional($this->created_at)->toISOString(),
            'updated_at' => optional($this->updated_at)->toISOString(),
            
            // Relación con la asignación de evidencia (solo cuando la solicitud es de tipo tradicional)
            'evidencia_asignacion' => $this->when(
                $this->evidencia_asignacion_id !== null,
                fn() => $this->whenLoaded('evidenceAssignment', fn() => $this->evidenceAssignment ? [
                    'evidencia_asignacion_id' => $this->evidenceAssignment->evidencia_asignacion_id,
                    'evidencia_id'            => $this->evidenceAssignment->evidencia_id,
                    'estado'                  => $this->evidenceAssignment->estado,
                    'fecha_limite'            => optional($this->evidenceAssignment->fecha_limite)->toISOString(),
                    'evidencia'               => $this->evidenceAssignment->evidence ? [
                        'evidencia_id' => $this->evidenceAssignment->evidence->evidencia_id,
                        'nomenclatura' => $this->evidenceAssignment->evidence->nomenclatura,
                        'descripcion'  => $this->evidenceAssignment->evidence->descripcion,
                    ] : null,
                ] : null)
            ),

            // Asignación de elemento (solo cuando la solicitud es de tipo flexible)
            'elemento_asignacion' => $this->when(
                $this->elemento_asignacion_id !== null,
                fn() => $this->whenLoaded('elementAssignment', fn() => $this->elementAssignment ? [
                    'elemento_asignacion_id' => $this->elementAssignment->elemento_asignacion_id,
                    'elemento_id'            => $this->elementAssignment->elemento_id,
                    'estado'                 => $this->elementAssignment->estado,
                    'fecha_limite'           => optional($this->elementAssignment->fecha_limite)->toISOString(),
                ] : null)
            ),

            // Usuario solicitante (cuando está cargado)
            'usuario' => [
                'usuario_id' => $this->whenLoaded('user', $this->user?->usuario_id),
                'nombre' => $this->whenLoaded('user', $this->user?->nombre),
                'email' => $this->whenLoaded('user', $this->user?->email),
            ],
            
            // Usuario que resolvió (cuando está cargado)
            'resolutor' => [
                'usuario_id' => $this->whenLoaded('resolutor', $this->resolutor?->usuario_id),
                'nombre' => $this->whenLoaded('resolutor', $this->resolutor?->nombre),
                'email' => $this->whenLoaded('resolutor', $this->resolutor?->email),
            ],
        ];
    }
}
