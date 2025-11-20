<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuditLogResource extends JsonResource
{
    /**
     * Transformar el recurso en un array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'bitacora_id' => $this->bitacora_id,
            'usuario' => [
                'usuario_id' => $this->user->usuario_id,
                'nombre' => $this->user->nombre,
                'email' => $this->user->email,
            ],
            'tipo_accion' => [
                'tipo_accion_id' => $this->actionType->tipo_accion_id,
                'descripcion' => $this->actionType->descripcion,
            ],
            'detalle' => $this->detalle,
            'fecha_hora' => $this->fecha_hora,
            'created_at' => $this->created_at,
        ];
    }
}
