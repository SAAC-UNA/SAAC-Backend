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
            // ----------- USUARIO -----------
            'usuario' => $this->user ? [
                'nombre' => $this->user->nombre ?? null,
                'email'  => $this->user->email,
                'roles'  => $this->user->getRoleNames()->values(),
            ] : null,

            // ----------- TIPO DE ACCIÓN (SEGURO) -----------

            'tipo_accion' =>$this->actionType ? [
                'tipo_accion_id' => $this->actionType->tipo_accion_id,
                'descripcion' => $this->actionType->descripcion ?? null,
            ]: null,
            
            'modulo' => $this->modulo,
            'detalle' => $this->detalle,
            'fecha_hora' => $this->fecha_hora,
            'created_at' => $this->created_at,
        ];
    }
}
