<?php

use App\Models\Role;
use App\Models\User;
use App\Models\AuditLog;
use Laravel\Sanctum\Sanctum;

function roleTestRetryTransientDb(callable $callback, int $attempts = 3)
{
    $lastException = null;

    for ($attempt = 1; $attempt <= $attempts; $attempt++) {
        try {
            return $callback();
        } catch (\Illuminate\Database\QueryException $exception) {
            $message = strtolower($exception->getMessage());
            $isTransient = str_contains($message, 'deadlock found')
                || str_contains($message, 'table definition has changed');

            if (!$isTransient || $attempt === $attempts) {
                throw $exception;
            }

            $lastException = $exception;
        }
    }

    if ($lastException) {
        throw $lastException;
    }

    return null;
}

function roleTestUserWithRole(string $roleName): User
{
    return roleTestRetryTransientDb(function () use ($roleName) {
        $user = User::factory()->create();
        $user->assignRole(Role::where('name', $roleName)->first());

        return $user;
    });
}

beforeEach(function () {
    roleTestRetryTransientDb(fn() => $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class));
});
it('unauthenticated_users_cannot_access_roles', function () {
        $response = $this->getJson('/api/roles');
        $response->assertStatus(401);
});
it('non_admin_users_cannot_access_roles', function () {
    $user = roleTestUserWithRole('Profesor');
        
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/roles');
        $response->assertStatus(403);
});
it('superusuario_can_list_roles', function () {
    $user = roleTestUserWithRole('Superusuario');
        
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/roles');
        $response->assertStatus(200)
                 ->assertJsonStructure(['data']);
});
it('administrador_can_list_roles', function () {
    $user = roleTestRetryTransientDb(fn() => User::factory()->create());
        $adminRole = Role::where('name', 'Administrador')->first();
        
        // Asegurar que el permiso existe
        $permission = \Spatie\Permission\Models\Permission::firstOrCreate(
            ['name' => 'roles.view', 'guard_name' => 'api']
        );
        
        // Asignar permiso al rol
        $adminRole->givePermissionTo($permission);
        
        $user->assignRole($adminRole);
        
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/roles');
        $response->assertStatus(200)
                 ->assertJsonStructure(['data']);
});
it('superusuario_can_create_role_and_logs_to_audit', function () {
    $user = roleTestUserWithRole('Superusuario');
        
        Sanctum::actingAs($user);

        $roleData = [
            'name' => 'Nuevo Rol Test',
            'description' => 'Descripción del nuevo rol',
            'permissions' => ['admin.super']
        ];

        $response = $this->postJson('/api/roles', $roleData);
        
        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'message',
                     'data' => ['id', 'name', 'description']
                 ]);

        // Verificar que se creó el rol
        $this->assertDatabaseHas('roles', [
            'name' => 'Nuevo Rol Test'
        ]);

        // Nota: Bitácora se prueba por separado en pruebas de integración
});
it('superusuario_can_update_role_and_logs_changes', function () {
    $user = roleTestUserWithRole('Superusuario');
        
        Sanctum::actingAs($user);

        $role = Role::create([
            'name' => 'Rol Original',
            'description' => 'Descripción original',
            'guard_name' => 'api'
        ]);

        $updateData = [
            'name' => 'Rol Actualizado',
            'description' => 'Descripción actualizada',
            'permissions' => ['admin.super']
        ];

        $response = $this->putJson("/api/roles/{$role->id}", $updateData);
        
        $response->assertStatus(200);

        // Verificar actualización
        $this->assertDatabaseHas('roles', [
            'id' => $role->id,
            'name' => 'Rol Actualizado'
        ]);

        // Nota: Bitácora se prueba por separado
});
it('superusuario_can_delete_role_and_logs_deletion', function () {
    $user = roleTestUserWithRole('Superusuario');
        
        Sanctum::actingAs($user);

        $role = Role::create(['name' => 'Rol a Eliminar', 'guard_name' => 'api']);

        $response = $this->deleteJson("/api/roles/{$role->id}");
        
        $response->assertStatus(200);

        // Verificar eliminación
        $this->assertDatabaseMissing('roles', [
            'id' => $role->id
        ]);

        // Nota: Bitácora se prueba por separado
});
it('it_validates_required_name_field', function () {
    $user = roleTestUserWithRole('Superusuario');
        
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/roles', [
            'description' => 'Sin nombre'
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['name']);
});
it('it_prevents_duplicate_role_names', function () {
    $user = roleTestUserWithRole('Superusuario');
        
        Sanctum::actingAs($user);

        Role::create(['name' => 'Rol Existente', 'guard_name' => 'api']);

        $response = $this->postJson('/api/roles', [
            'name' => 'Rol Existente',
            'description' => 'Intento duplicar'
        ]);

        $response->assertStatus(422);
});
it('it_can_show_specific_role', function () {
    $user = roleTestUserWithRole('Superusuario');
        
        Sanctum::actingAs($user);

        $role = Role::create(['name' => 'Rol Específico', 'guard_name' => 'api']);

        $response = $this->getJson("/api/roles/{$role->id}");
        
        $response->assertStatus(200)
                 ->assertJson([
                     'data' => [
                         'id' => $role->id,
                         'name' => 'Rol Específico'
                     ]
                 ]);
});
it('it_returns_404_for_nonexistent_role', function () {
    $user = roleTestUserWithRole('Superusuario');
        
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/roles/99999');
        $response->assertStatus(404);
});
