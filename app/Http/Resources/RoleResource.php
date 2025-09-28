<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class RoleResource extends JsonResource
{
    /**
     * Transforma el modelo Role en un array JSON.
     */
    public function toArray($request)
    {
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'description' => $this->description,
            'permissions' => $this->permissions->map(function ($permiso) {
                return [
                    'id'    => $permiso->id,
                    'name'  => $permiso->name,
                    'label' => config('permissions.descriptions')[$permiso->name] ?? $permiso->name,
                ];
            }),
        ];
    }
}
