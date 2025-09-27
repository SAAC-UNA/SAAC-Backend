<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Spatie\Permission\Models\Permission;
use App\Models\Role;
use PHPUnit\Framework\Attributes\Test;

/**
 * Pruebas para el RoleController.
 */
class RoleControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function creaRolCorrectamente()
    {
        $permiso = Permission::create(['name' => 'gestion_roles', 'guard_name' => 'api']);

        $response = $this->postJson('/api/roles/crear', [
            'name' => 'Administrador',
            'description' => 'Rol con acceso total',
            'permissions' => [$permiso->name],
        ]);

        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'mensaje',
                     'datos' => [
                         'id',
                         'name',
                         'description',
                         'permissions' => [
                             '*' => ['id', 'name', 'label']
                         ]
                     ],
                 ]);

        $this->assertDatabaseHas('roles', ['name' => 'Administrador']);
    }

    #[Test]
    public function rechazaRolSinPermisos()
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
    public function listaRolesCorrectamente()
    {
        $permiso = Permission::create(['name' => 'gestion_roles', 'guard_name' => 'api']);
        $rol = Role::create(['name' => 'Editor', 'description' => 'Rol inicial', 'guard_name' => 'api']);
        $rol->syncPermissions([$permiso->name]);

        $response = $this->getJson('/api/roles');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'datos' => [[
                         'id',
                         'name',
                         'description',
                         'permissions' => [
                             '*' => ['id', 'name', 'label']
                         ]
                     ]]
                 ]);
    }

    #[Test]
    public function muestraRolCorrectamente()
    {
        $permiso = Permission::create(['name' => 'gestion_roles', 'guard_name' => 'api']);
        $rol = Role::create(['name' => 'Supervisor', 'description' => 'Rol con permisos de gestión', 'guard_name' => 'api']);
        $rol->syncPermissions([$permiso->name]);

        $response = $this->getJson("/api/roles/{$rol->id}");

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'datos' => [
                         'id',
                         'name',
                         'description',
                         'permissions' => [
                             '*' => ['id', 'name', 'label']
                         ]
                     ]
                 ]);
    }

    #[Test]
    public function rechazaMostrarRolInexistente()
    {
        $response = $this->getJson('/api/roles/999');

        $response->assertStatus(404)
                 ->assertJson(['mensajeError' => 'Rol no encontrado']);
    }

    #[Test]
    public function actualizaRolCorrectamente()
    {
        $permiso1 = Permission::create(['name' => 'gestion_roles', 'guard_name' => 'api']);
        $permiso2 = Permission::create(['name' => 'gestion_usuarios', 'guard_name' => 'api']);

        $rol = Role::create(['name' => 'Editor', 'description' => 'Rol inicial', 'guard_name' => 'api']);
        $rol->syncPermissions([$permiso1->name]);

        $response = $this->putJson("/api/roles/{$rol->id}", [
            'name' => 'Editor actualizado',
            'description' => 'Rol actualizado',
            'permissions' => [$permiso2->name],
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'mensaje' => 'Rol actualizado con éxito',
                     'datos' => ['name' => 'Editor actualizado'],
                 ]);

        $this->assertDatabaseHas('roles', ['name' => 'Editor actualizado']);
    }

    #[Test]
    public function rechazaActualizarRolInexistente()
    {
        $permiso = Permission::create(['name' => 'gestion_roles', 'guard_name' => 'api']);

        $response = $this->putJson('/api/roles/999', [
            'name' => 'NoExiste',
            'description' => 'Rol inválido',
            'permissions' => [$permiso->name],
        ]);

        $response->assertStatus(404)
                 ->assertJson(['mensajeError' => 'Rol no encontrado']);
    }

    #[Test]
    public function eliminaRolCorrectamente()
    {
        $rol = Role::create(['name' => 'Temporal', 'description' => 'Rol temporal', 'guard_name' => 'api']);

        $response = $this->deleteJson("/api/roles/{$rol->id}");

        $response->assertStatus(200)
                 ->assertJson(['mensaje' => 'Rol eliminado con éxito']);

        $this->assertDatabaseMissing('roles', ['id' => $rol->id]);
    }

    #[Test]
    public function rechazaEliminarRolInexistente()
    {
        $response = $this->deleteJson('/api/roles/999');

        $response->assertStatus(404)
                 ->assertJson(['mensajeError' => 'Rol no encontrado']);
    }

    #[Test]
    public function rechazaRolSinNombre()
    {
        $permiso = Permission::create(['name' => 'gestion_roles', 'guard_name' => 'api']);

        $response = $this->postJson('/api/roles/crear', [
            'name' => '',
            'description' => 'Rol inválido',
            'permissions' => [$permiso->name],
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['name']);
    }

    #[Test]
    public function rechazaRolConNombreDemasiadoLargo()
    {
        $permiso = Permission::create(['name' => 'gestion_roles', 'guard_name' => 'api']);

        $response = $this->postJson('/api/roles/crear', [
            'name' => str_repeat('a', 256), // 256 caracteres
            'description' => 'Rol con nombre muy largo',
            'permissions' => [$permiso->name],
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['name']);
    }

    #[Test]
    public function rechazaRolDuplicado()
    {
        $permiso = Permission::create(['name' => 'gestion_roles', 'guard_name' => 'api']);

        // Crear un rol válido
        $this->postJson('/api/roles/crear', [
            'name' => 'Duplicado',
            'description' => 'Primer rol',
            'permissions' => [$permiso->name],
        ])->assertStatus(201);

        // Intentar crear otro con el mismo nombre
        $response = $this->postJson('/api/roles/crear', [
            'name' => 'Duplicado',
            'description' => 'Segundo rol',
            'permissions' => [$permiso->name],
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['name']);
    }

    #[Test]
    public function rechazaRolConPermisoInvalido()
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
