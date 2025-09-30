<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Spatie\Permission\Models\Permission;
use App\Models\Role;
use PHPUnit\Framework\Attributes\Test;

/**
 * Functional tests for the RoleController.
 *
 * Validates endpoints related to roles and their permissions:
 * creation, reading, updating and deletion, plus validation rules.
 */

class RoleFeatureTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function createRoleSuccessfully()
    {
        $permission = Permission::create(['name' => 'gestion_roles', 'guard_name' => 'api']);

        $response = $this->postJson('/api/roles/crear', [
            'name' => 'Administrador',
            'description' => 'Rol con acceso total',
            'permissions' => [$permission->name],
        ]);

        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'message',
                     'data' => [
                         'id',
                         'name',
                         'description',
                         'permissions' => [
                             '*' => ['id', 'name', 'label'],
                         ],
                     ],
                 ]);

        $this->assertDatabaseHas('roles', ['name' => 'Administrador']);
    }

    #[Test]
    public function rejectRoleWithoutPermissions()
    {
        $response = $this->postJson('/api/roles/crear', [
            'name' => 'Profesor',
            'description' => 'Encargado de cursos y subir evidencias',
            'permissions' => [],
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['permissions']);
    }

    #[Test]
    public function listRolesSuccessfully()
    {
        $permission = Permission::create(['name' => 'gestion_roles', 'guard_name' => 'api']);
        $role = Role::create(['name' => 'Editor', 'description' => 'Rol inicial', 'guard_name' => 'api']);
        $role->syncPermissions([$permission->name]);

        $response = $this->getJson('/api/roles');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'data' => [[
                         'id',
                         'name',
                         'description',
                         'permissions' => [
                             '*' => ['id', 'name', 'label'],
                         ],
                     ]],
                 ]);
    }

    #[Test]
    public function showRoleSuccessfully()
    {
        $permission = Permission::create(['name' => 'gestion_roles', 'guard_name' => 'api']);
        $role = Role::create(['name' => 'Supervisor', 'description' => 'Rol con permisos de gestión', 'guard_name' => 'api']);
        $role->syncPermissions([$permission->name]);

        $response = $this->getJson("/api/roles/{$role->id}");

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'data' => [
                         'id',
                         'name',
                         'description',
                         'permissions' => [
                             '*' => ['id', 'name', 'label'],
                         ],
                     ],
                 ]);
    }

    #[Test]
    public function rejectShowNonExistentRole()
    {
        $response = $this->getJson('/api/roles/999');

        $response->assertStatus(404)
                 ->assertJson([
                     'error'   => 'Not Found',
                     'message' => 'Rol no encontrado',
                 ]);
    }

    #[Test]
    public function updateRoleSuccessfully()
    {
        $permission1 = Permission::create(['name' => 'gestion_roles', 'guard_name' => 'api']);
        $permission2 = Permission::create(['name' => 'gestion_usuarios', 'guard_name' => 'api']);

        $role = Role::create(['name' => 'Editor', 'description' => 'Rol inicial', 'guard_name' => 'api']);
        $role->syncPermissions([$permission1->name]);

        $response = $this->putJson("/api/roles/{$role->id}", [
            'name' => 'Editor actualizado',
            'description' => 'Rol actualizado',
            'permissions' => [$permission2->name],
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'message' => 'Rol actualizado con éxito',
                     'data'    => ['name' => 'Editor actualizado'],
                 ]);

        $this->assertDatabaseHas('roles', ['name' => 'Editor actualizado']);
    }

    #[Test]
    public function rejectUpdateNonExistentRole()
    {
        $permission = Permission::create(['name' => 'gestion_roles', 'guard_name' => 'api']);

        $response = $this->putJson('/api/roles/999', [
            'name' => 'NoExiste',
            'description' => 'Rol inválido',
            'permissions' => [$permission->name],
        ]);

        $response->assertStatus(404)
                 ->assertJson([
                     'error'   => 'Not Found',
                     'message' => 'Rol no encontrado',
                 ]);
    }

    #[Test]
    public function deleteRoleSuccessfully()
    {
        $role = Role::create(['name' => 'Temporal', 'description' => 'Rol temporal', 'guard_name' => 'api']);

        $response = $this->deleteJson("/api/roles/{$role->id}");

        $response->assertStatus(200)
                 ->assertJson(['message' => 'Rol eliminado con éxito']);

        $this->assertDatabaseMissing('roles', ['id' => $role->id]);
    }

    #[Test]
    public function rejectDeleteNonExistentRole()
    {
        $response = $this->deleteJson('/api/roles/999');

        $response->assertStatus(404)
                 ->assertJson([
                     'error'   => 'Not Found',
                     'message' => 'Rol no encontrado',
                 ]);
    }

    #[Test]
    public function rejectRoleWithoutName()
    {
        $permission = Permission::create(['name' => 'gestion_roles', 'guard_name' => 'api']);

        $response = $this->postJson('/api/roles/crear', [
            'name' => '',
            'description' => 'Rol inválido',
            'permissions' => [$permission->name],
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['name']);
    }

    #[Test]
    public function rejectRoleWithTooLongName()
    {
        $permission = Permission::create(['name' => 'gestion_roles', 'guard_name' => 'api']);

        $response = $this->postJson('/api/roles/crear', [
            'name' => str_repeat('a', 256),
            'description' => 'Rol con nombre muy largo',
            'permissions' => [$permission->name],
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['name']);
    }

    #[Test]
    public function rejectDuplicateRole()
    {
        $permission = Permission::create(['name' => 'gestion_roles', 'guard_name' => 'api']);

        $this->postJson('/api/roles/crear', [
            'name' => 'Duplicado',
            'description' => 'Primer rol',
            'permissions' => [$permission->name],
        ])->assertStatus(201);

        $response = $this->postJson('/api/roles/crear', [
            'name' => 'Duplicado',
            'description' => 'Segundo rol',
            'permissions' => [$permission->name],
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['name']);
    }

    #[Test]
    public function rejectRoleWithInvalidPermission()
    {
        $response = $this->postJson('/api/roles/crear', [
            'name' => 'ConPermisoInvalido',
            'description' => 'Rol con permiso que no existe',
            'permissions' => ['permiso_que_no_existe'],
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['permissions.0']);
}
}