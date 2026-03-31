<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CriterionResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'            => $this->criterio_id,
            'componente_id' => $this->componente_id,
            'comentario_id' => $this->comentario_id,
            'descripcion'   => $this->descripcion,
            'nomenclatura'  => $this->nomenclatura,
            'activo'        => $this->activo ?? true,

            // Solo se incluyen si están eager-loaded (no rompe otros endpoints)
            'component'  => $this->whenLoaded('component', function () {
                $comp = $this->component;
                return [
                    'componente_id' => $comp->componente_id,
                    'nombre'        => $comp->nombre,
                    'nomenclatura'  => $comp->nomenclatura,
                    'dimension'     => $comp->relationLoaded('dimension') && $comp->dimension
                        ? [
                            'dimension_id' => $comp->dimension->dimension_id,
                            'nombre'       => $comp->dimension->nombre,
                            'nomenclatura' => $comp->dimension->nomenclatura,
                        ]
                        : null,
                ];
            }),
            'standards' => $this->whenLoaded('standards', fn () =>
                $this->standards->map(fn ($s) => [
                    'estandar_id' => $s->estandar_id,
                    'descripcion' => $s->descripcion,
                    'activo'      => $s->activo,
                ])->values()
            ),
            'estado'        => $this->estado,
            'activo'        => $this->activo ?? true, // Agregar campo activo
        ];
    }
}
