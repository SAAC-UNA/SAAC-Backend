<?php

namespace App\Http\Resources;

use App\Support\AccessResolver;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $allPermissionCollection = $this->getAllPermissions();
        $allPermissionNames = $allPermissionCollection->pluck('name')->values()->all();

        return [
            'id'          => $this->usuario_id,
            'name'        => $this->nombre,
            'email'       => $this->email,
            'status'      => $this->status,
            'cedula'      => $this->cedula,
            'created_at'  => $this->created_at?->toIso8601String(),
            'updated_at'  => $this->updated_at?->toIso8601String(),
            
            // Roles del usuario
            'roles' => $this->roles->map(function ($role) {
                return [
                    'id' => $role->id,
                    'name' => $role->name,
                ];
            }),
            'careers' => $this->careers->map(function ($careerCampus) {
                return [
                    'carrera_sede_id' => $careerCampus->carrera_sede_id,
                    'carrera_id'      => $careerCampus->carrera_id,
                    'nombre'          => $careerCampus->career?->nombre,
                ];
            })->values(),
            'direct_permissions' => $this->permissions->map(function ($permission) {
                $descriptions = config('permissions.descriptions', []);
                return [
                    'id'    => $permission->id,
                    'name'  => $permission->name,
                    'label' => $descriptions[$permission->name] ?? $permission->name,
                ];
            }),
            'all_permissions' => $allPermissionCollection->map(function ($permission) {
                $descriptions = config('permissions.descriptions', []);
                return [
                    'id'    => $permission->id,
                    'name'  => $permission->name,
                    'label' => $descriptions[$permission->name] ?? $permission->name,
                ];
            }),
            'all_capabilities' => AccessResolver::resolveCapabilities($allPermissionNames),
        ];
    }
}
