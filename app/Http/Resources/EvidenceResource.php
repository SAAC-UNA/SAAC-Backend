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
            'created_at'          => optional($this->created_at)->toISOString(),
            'updated_at'          => optional($this->updated_at)->toISOString(),
            // Si luego activas relaciones, aquí puedes anidar:
            // 'criterion'      => new CriterionResource($this->whenLoaded('criterion')),
            // 'evidence_state' => new EvidenceStateResource($this->whenLoaded('evidenceState')),
        ];
    }
}
