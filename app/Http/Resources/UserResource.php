<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource para transformar un modelo User en un array JSON estructurado.
 *
 * Este recurso define cómo se exponen los atributos del modelo User a través
 * de la API, asegurando consistencia en la salida de datos y aplicando
 * las etiquetas legibles de permisos.
 */
class UserResource extends JsonResource
{
    /**
     * Transforma el modelo User en un array JSON listo para respuesta API.
     *
     * @param \Illuminate\Http\Request $request Objeto de la petición actual.
     * @return array<string,mixed> Representación del usuario en formato JSON.
     */
    public function toArray($request): array
    {
        return [
            'id'          => $this->usuario_id,
            'name'        => $this->nombre,
            'email'       => $this->email,
            'status'      => $this->status,
            'cedula'      => $this->cedula,
            'created_at'  => $this->created_at,
            'updated_at'  => $this->updated_at,
            
            // Roles del usuario
            'roles' => $this->roles->map(function ($role) {
                return [
                    'id'   => $role->id,
                    'name' => $role->name,
                ];
            }),
            
            // Permisos directos con etiquetas legibles
            'direct_permissions' => $this->permissions->map(function ($permission) {
                return [
                    'id'    => $permission->id,
                    'name'  => $permission->name,
                    'label' => config('permissions.descriptions')[$permission->name] ?? $permission->name,
                ];
            }),
            
            // Todos los permisos efectivos (directos + por roles) con etiquetas legibles
            'all_permissions' => $this->getAllPermissions()->map(function ($permission) {
                return [
                    'id'    => $permission->id,
                    'name'  => $permission->name,
                    'label' => config('permissions.descriptions')[$permission->name] ?? $permission->name,
                ];
            }),
        ];
    }
}