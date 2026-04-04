<?php

namespace App\Services;

use App\Models\Role;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Illuminate\Database\Eloquent\Model;

/**
 * Servicio que gestiona la lógica de negocio relacionada con Roles.
 * Incluye operaciones para listar, obtener, crear, actualizar y eliminar roles,
 * así como la gestión de permisos asociados.
 */
class RoleService
{
    public function __construct()
    {
        // Preparado para futuras dependencias (ej: logger, auditoría, etc.)
    }

    /**
     * Listar todos los roles con sus permisos asociados.
     *
     * @return \Illuminate\Support\Collection Colección de roles con permisos.
     */
    public function listRoles()
    {
        return Role::with('permissions:id,name')->get();
    }

    /**
     * Obtener un rol específico por ID con sus permisos.
     *
     * @param int $id Identificador único del rol.
     * @return Role|null Retorna el rol o null si no existe.
     */
    public function getRole(int $id): ?Role
    {
        return Role::with('permissions')->find($id);
    }

    /**
     * Crear un nuevo rol con sus permisos.
     * Se usa transacción para garantizar atomicidad en el proceso.
     *
     * @param array<string,mixed> $data Datos validados del rol.
     * @return Role Rol recién creado.
     */
    public function createRole(array $data): Role
    {
        return DB::transaction(function () use ($data) {
            $role = Role::create([
                'name'        => $data['name'],
                'description' => $data['description'] ?? null,
                'guard_name'  => 'api',
            ]);

            // Si se especifican permisos, se asignan al rol
            if (!empty($data['permissions'])) {
                $role->syncPermissions($data['permissions']);
            }

            return $role->refresh();
        });
    }

    /**
     * Actualizar un rol existente.
     * Solo se ejecuta la actualización si hay cambios reales.
     *
     * @param Role $role Rol a actualizar.
     * @param array<string,mixed> $data Nuevos datos del rol.
     * @return Role Rol actualizado.
     */
    public function updateRole(Role $role, array $data): Role
    {
        return DB::transaction(function () use ($role, $data) {
            $original = [
                'name'        => $role->name,
                'description' => $role->description,
                'permissions' => $role->permissions->pluck('name')->sort()->values()->toArray(),
            ];

            $newData = [
                'name'        => $data['name'] ?? $role->name,
                'description' => $data['description'] ?? null,
                'permissions' => collect($data['permissions'] ?? $original['permissions'])
                                    ->sort()->values()->toArray(),
            ];

            // Se evita actualizar si los datos son idénticos
            if ($original != $newData) {
                $role->update([
                    'name'        => $newData['name'],
                    'description' => $newData['description'],
                ]);

                $role->syncPermissions($newData['permissions']);
            }

            return $role->refresh();
        });
    }

    /**
     * Listar todos los permisos disponibles con sus etiquetas legibles.
     *
     * @return \Illuminate\Support\Collection Colección de permisos con name y label.
     */
    public function listPermissions()
    {
        // Retorna TODOS los permisos del sistema
        // Esto permite que al editar roles se muestren todos los permisos asignados correctamente
        return Permission::query()
        ->where('guard_name', 'api')
        ->orderBy('name')
        ->get(['id','name'])
        ->map(function ($permission) {
            return [
                'id'    => $permission->id,
                'name'  => $permission->name,
                'label' => config('permissions.descriptions')[$permission->name] ?? $permission->name,
            ];
        });
    }

    /**
     * Obtener la estructura de módulos y permisos desde la configuración.
     * Útil para construir interfaces de gestión de roles.
     *
     * @return array Estructura de módulos con sus permisos y descripciones.
     */
    public function getModulesStructure(): array
    {
        $modules = config('permissions.modules', []);
        $descriptions = config('permissions.descriptions', []);
        
        $structure = [];
        
        foreach ($modules as $module => $actions) {
            $modulePermissions = [];

            foreach ($actions as $action) {
                $permissionName = "{$module}.{$action}";
                $modulePermissions[] = [
                    'name' => $permissionName,
                    'action' => $action,
                    'label' => $descriptions[$permissionName] ?? $permissionName,
                ];
            }

            $structure[] = [
                'group' => $module,
                'name' => ucfirst(str_replace('_', ' ', $module)),
                'description' => null,
                'permissions' => $modulePermissions,
            ];
        }
        
        return $structure;
    }

    /**
     * Verificar si un rol es protegido (roles por defecto del sistema).
     * Los roles protegidos no pueden ser eliminados y su nombre no puede cambiar.
     *
     * @param Role|string $role Instancia del rol o nombre del rol.
     * @return bool True si el rol está protegido.
     */
    public function isProtectedRole($role): bool
    {
        $protectedRoles = [
            'Superusuario',
            'Administrador',
            'Encargado de Acreditación',
            'Profesor',
        ];
        
        $roleName = $role instanceof Role ? $role->name : $role;
        
        return in_array($roleName, $protectedRoles, true);
    }

    /**
     * Obtener roles agrupados por tipo (protegidos vs personalizados).
     *
     * @return array Array con collections de roles protegidos y personalizados.
     */
    public function getRolesGrouped(): array
    {
        $roles = $this->listRoles();
        
        return [
            'protected' => $roles->filter(fn($role) => $this->isProtectedRole($role)),
            'custom' => $roles->reject(fn($role) => $this->isProtectedRole($role)),
        ];
    }

    /**
     * Validar si se puede eliminar un rol.
     * No se pueden eliminar roles protegidos ni roles con usuarios asignados.
     *
     * @param Role $role Rol a validar.
     * @return array ['can_delete' => bool, 'reason' => string|null]
     */
    public function canDeleteRole(Role $role): array
    {
        // Verificar si es un rol protegido
        if ($this->isProtectedRole($role)) {
            return [
                'can_delete' => false,
                'reason' => 'Los roles del sistema no pueden ser eliminados.',
            ];
        }
        
        // Verificar si tiene usuarios asignados
        $usersCount = $role->users()->count();
        if ($usersCount > 0) {
            return [
                'can_delete' => false,
                'reason' => "Este rol tiene {$usersCount} usuario(s) asignado(s). Debes reasignarlos antes de eliminar el rol.",
            ];
        }
        
        return [
            'can_delete' => true,
            'reason' => null,
        ];
    }

    /**
     * Eliminar un rol existente por su ID.
     * Se desasocian permisos antes de la eliminación y se manejan eventos.
     *
     * @param int $id Identificador del rol a eliminar.
     * @return Role|null Rol eliminado o null si no existe.
     */
    public function deleteRole(int $id): ?Role
    {
        return DB::transaction(function () use ($id) {
            $role = Role::find($id);

            if (!$role) {
                return null;
            }

            // Se desactiva el despachador de eventos para evitar conflictos con Spatie
            Model::unsetEventDispatcher();

            $role->permissions()->detach();
            $role->delete();

            // Se restaura el despachador de eventos
            Model::setEventDispatcher(app('events'));

            return $role;
        });
    }
}
