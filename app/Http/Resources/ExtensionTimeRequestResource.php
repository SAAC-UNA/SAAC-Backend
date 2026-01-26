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
                'nombre' => $this->user?->nombre,
                'cedula' => $this->user?->cedula,
                'email' => $this->user?->email,
            ]),
            'evidencia_asignacion_id' => $this->evidencia_asignacion_id,
             
            // Estado de la solicitud
            'estado' => $this->estado,
            'motivo' => $this->motivo,
            
            // Fechas clave
            'fecha_solicitud' => $this->created_at?->format('Y-m-d H:i:s'),
            'fecha_sugerida' => $this->fecha_sugerida?->format('Y-m-d'),
            
            // Campos del encargado (RF-16)
            'fecha_resolucion' => $this->fecha_resolucion?->format('Y-m-d H:i:s'),
            'justificacion' => $this->justificacion,
            
            // Timestamps
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
