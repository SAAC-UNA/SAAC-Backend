<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Policy para autorización de gestión de usuarios (HU-002)
 * 
 * Define las políticas de acceso para las operaciones implementadas:
 * - viewAny: Listar todos los usuarios (index)
 * - update: Activar, desactivar, asignar roles y permisos
 * 
 * Nota: Los métodos CRUD (create, store, show, edit, destroy) no están 
 * implementados en UserController, por lo que no tienen políticas definidas.
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
}
