<?php

namespace App\Http\Resources;

use App\Models\EvidenceAssignment;
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
            'estado' => EvidenceAssignment::apiStatusFromDb($this->estado),
            'fecha_asignacion' => optional($this->fecha_asignacion)->toISOString(),
            'fecha_limite' => optional($this->fecha_limite)->toISOString(),
            'comentario' => $this->comentario,
            'created_at' => optional($this->created_at)->toISOString(),
            'updated_at' => optional($this->updated_at)->toISOString(),
            
            // Relaciones anidadas: se usan datos de la relación cargada (Eloquent)
            // o los campos planos que provienen del SP cuando no hay eager loading.
            'proceso' => [
                'proceso_id'            => $this->whenLoaded('process', $this->process?->proceso_id),
                'ciclo_acreditacion_id' => $this->whenLoaded('process', $this->process?->ciclo_acreditacion_id),
            ],
            'evidencia' => $this->relationLoaded('evidence') && $this->evidence
                ? new EvidenceResource($this->evidence)
                : [
                    'evidencia_id'  => $this->evidencia_id,
                    'nomenclatura'  => $this->evidencia_nomenclatura ?? null,
                    'descripcion'   => $this->evidencia_descripcion  ?? null,
                    // MODELO FLEXIBLE (HU-007): exponer ambos anchors para que el front
                    // sepa si la evidencia es tradicional (criterio_id) o flexible (elemento_id)
                    'criterio_id'   => $this->criterio_id             ?? null,
                    'elemento_id'   => $this->elemento_id             ?? null,
                    'criterion'     => null,
                ],
            'usuario' => $this->relationLoaded('user') && $this->user
                ? [
                    'usuario_id' => $this->user->usuario_id,
                    'nombre'     => $this->user->nombre,
                    'email'      => $this->user->email,
                ]
                : [
                    'usuario_id' => $this->usuario_id,
                    'nombre'     => $this->usuario_nombre ?? null,
                    'email'      => $this->usuario_email  ?? null,
                ],
            
            // HU-016: Indicar si tiene una solicitud de ampliación pendiente
            // El servicio carga este valor via withExists() — sin N+1
            'has_pending_extension_request' => (bool) $this->has_pending_extension_request,
            // Indica si ya existe al menos un archivo/enlace subido por el responsable
            'has_uploaded_files' => (bool) $this->has_uploaded_files,
        ];
    }
}
