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
            'activo'        => $this->activo ?? true, // Agregar campo activo
        ];
    }
}
