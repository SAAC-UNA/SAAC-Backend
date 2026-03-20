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
            // Relaciones opcionales — usa la relación Eloquent si está cargada,
            // o los campos planos que devuelve el SP cuando no hay eager loading.
            'criterion'           => $this->relationLoaded('criterion') && $this->criterion
                ? new CriterionResource($this->criterion)
                : ($this->criterio_nomenclatura !== null
                    ? [
                        'id'           => $this->criterio_id,
                        'nomenclatura' => $this->criterio_nomenclatura,
                        'descripcion'  => $this->criterio_descripcion ?? null,
                        'activo'       => true,
                    ]
                    : null),
            'estado_evidencia'    => $this->relationLoaded('evidenceState') && $this->evidenceState
                ? ['nombre' => $this->evidenceState->nombre]
                : ($this->estado_nombre !== null
                    ? ['nombre' => $this->estado_nombre]
                    : null),
            'responsables'        => $this->when(
                $this->relationLoaded('assignments'),
                fn() => $this->assignments->map(fn($assignment) => [
                    'usuario_id' => $assignment->user->usuario_id,
                    'nombre'     => $assignment->user->nombre,
                    'email'      => $assignment->user->email,
                ])
            ),
            'roles_acceso'        => $this->when(
                $this->relationLoaded('assignments'),
                fn() => $this->assignments
                    ->filter(fn($a) => $a->relationLoaded('user') && $a->user?->relationLoaded('roles'))
                    ->flatMap(fn($a) => $a->user->roles->pluck('name'))
                    ->unique()
                    ->values()
                    ->toArray()
            ),
            // Contadores de recursos
            'archivos_count'      => $this->archivos_count ?? 0,
            'enlaces_count'       => $this->enlaces_count ?? 0,
        ];
    }
}
