<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Policy para autorización de gestión de usuarios (HU-002)
 * 
 * Permisos utilizados:
 * - usuarios.view: Ver listado de usuarios
 * - usuarios.create: Crear nuevos usuarios
 * - usuarios.edit: Editar usuarios, asignar roles y permisos
 * - usuarios.delete: Eliminar usuarios
 */
class UserPolicy
{
    /**
     * Determine whether the user can view any models.
     * Permite listar todos los usuarios (GET /api/admin/users)
     */
    public function viewAny(User $user): bool
    {
        return $user->can('usuarios.view');
    }

    /**
     * Determine whether the user can view a specific user.
     */
    public function view(User $user, User $model): bool
    {
        return $user->can('usuarios.view');
    }

    /**
     * Determine whether the user can create users.
     */
    public function create(User $user): bool
    {
        return $user->can('usuarios.create');
    }

    /**
     * Determine whether the user can update the model.
     * Permite:
     * - Activar usuarios (PATCH /api/admin/users/{id}/activate)
     * - Desactivar usuarios (PATCH /api/admin/users/{id}/deactivate)
     * - Asignar roles (PUT /api/admin/users/{id}/role)
     * - Asignar permisos (PUT /api/admin/users/{id}/permissions)
     */
    public function update(User $user, User $model): bool
    {
        return $user->can('usuarios.edit');
    }

    /**
     * Determine whether the user can delete users.
     */
    public function delete(User $user, User $model): bool
    {
        return $user->can('usuarios.delete');
    }
}
