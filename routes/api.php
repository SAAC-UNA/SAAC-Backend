<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RoleController;

Route::prefix('roles')->group(function () {
    // Listar todos los roles
    Route::get('/', [RoleController::class, 'listRoles'])->name('roles.index');

    // Crear un nuevo rol
    Route::post('/crear', [RoleController::class, 'createRole'])->name('roles.create');

    // Listar todos los permisos disponibles
    Route::get('/permisos', [RoleController::class, 'listPermissions'])->name('roles.permissions');

    // Mostrar un rol específico
    Route::get('/{id}', [RoleController::class, 'showRole'])->name('roles.show');

    // Actualizar un rol existente
    Route::put('/{id}', [RoleController::class, 'updateRole'])->name('roles.update');

    // Eliminar un rol
    Route::delete('/{id}', [RoleController::class, 'deleteRole'])->name('roles.delete');
});
