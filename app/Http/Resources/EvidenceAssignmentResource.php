<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EvidenceAssignmentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'evidencia_asignacion_id' => $this->evidencia_asignacion_id,
            'proceso_id' => $this->proceso_id,
            'evidencia_id' => $this->evidencia_id,
            'usuario_id' => $this->usuario_id,
            'estado' => $this->estado,
            'fecha_asignacion' => optional($this->fecha_asignacion)->toISOString(),
            'fecha_limite' => optional($this->fecha_limite)->toISOString(),
            'created_at' => optional($this->created_at)->toISOString(),
            'updated_at' => optional($this->updated_at)->toISOString(),
            
            // Relaciones anidadas cuando están cargadas
            'proceso' => [
                'proceso_id' => $this->whenLoaded('process', $this->process?->proceso_id),
                'ciclo_acreditacion_id' => $this->whenLoaded('process', $this->process?->ciclo_acreditacion_id),
            ],
            'evidencia' => new EvidenceResource($this->whenLoaded('evidence')),
            'usuario' => [
                'usuario_id' => $this->whenLoaded('user', $this->user?->usuario_id),
                'nombre' => $this->whenLoaded('user', $this->user?->nombre),
                'email' => $this->whenLoaded('user', $this->user?->email),
            ],
        ];
    }
}
