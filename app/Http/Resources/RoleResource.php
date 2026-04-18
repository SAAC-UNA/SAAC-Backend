<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource para transformar un modelo Role en un array JSON estructurado.
 *
 * Este recurso define cómo se exponen los atributos del modelo Role a través
 * de la API, asegurando consistencia en la salida de datos.
 */
class RoleResource extends JsonResource
{
    /**
     * Transforma el modelo Role en un array JSON listo para respuesta API.
     *
     * @param \Illuminate\Http\Request $request Objeto de la petición actual.
     * @return array<string,mixed> Representación del rol en formato JSON.
     */
    public function toArray($request): array
    {
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'description' => $this->description,
            'status'      => $this->status,
            // Permisos con estructura completa para el frontend (id, name, label)
            'permissions' => $this->permissions->map(function ($permission) {
                $descriptions = config('permissions.descriptions', []);
                return [
                    'id'    => $permission->id,
                    'name'  => $permission->name,
                    'label' => $descriptions[$permission->name] ?? $permission->name,
                ];
            }),
              'creadoEl'  => $this->created_at?->format('Y-m-d H:i:s'),
             'actualizadoEl' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
