<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\PermissionController;

Route::prefix('roles')->group(function () {
    // Listar roles
    Route::get('/', [RoleController::class, 'listarRoles'])->name('roles.index');

    // Crear rol
    Route::post('/crear', [RoleController::class, 'crearRol'])->name('roles.guardar');

    // Listar permisos (endpoint adicional) - primero las rutas fijas
    Route::get('/permisos', [RoleController::class, 'listarPermisos'])->name('roles.permisos');

    // Mostrar rol específico
    Route::get('/{id}', [RoleController::class, 'mostrarRol'])->name('roles.mostrar');

    // Actualizar rol
    Route::put('/{id}', [RoleController::class, 'actualizarRol'])->name('roles.actualizar');
});

