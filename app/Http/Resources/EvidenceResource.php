<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EvidenceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'evidencia_id'        => $this->evidencia_id,
            'criterio_id'         => $this->criterio_id,
            'estado_evidencia_id' => $this->estado_evidencia_id,
            'descripcion'         => $this->descripcion,
            'nomenclatura'        => $this->nomenclatura,
            'activo'              => $this->activo ?? true,
            'fecha_publicacion'   => optional($this->created_at)->toISOString(),
            'updated_at'          => optional($this->updated_at)->toISOString(),
            // Relaciones opcionales
            'criterion'           => new CriterionResource($this->whenLoaded('criterion')),
            'estado_evidencia'    => $this->when(
                $this->relationLoaded('evidenceState') && $this->evidenceState,
                fn() => ['nombre' => $this->evidenceState->nombre]
            ),
            'responsables'        => $this->when(
                $this->relationLoaded('assignments'),
                fn() => $this->assignments->map(fn($assignment) => [
                    'usuario_id' => $assignment->user->usuario_id,
                    'nombre'     => $assignment->user->nombre,
                    'email'      => $assignment->user->email,
                ])
            ),
            // Contadores de recursos
            'archivos_count'      => $this->archivos_count ?? 0,
            'enlaces_count'       => $this->enlaces_count ?? 0,
        ];
    }
}
