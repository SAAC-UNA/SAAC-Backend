<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Role;
use App\Services\RoleService;
use PHPUnit\Framework\Attributes\Test;

/**
 * Pruebas unitarias para la capa de servicio de Roles.
 *
 * Este conjunto de pruebas valida la lógica de negocio implementada
 * en el servicio RoleService: creación, actualización, eliminación,
 * listado y obtención de roles, además de la gestión de permisos.
 */
class RoleUnitTest extends TestCase
{
    use RefreshDatabase;

    private RoleService $service;

    /**
     * Configuración inicial de cada prueba.
     * Se ejecuta antes de cada test unitario.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Cargar los permisos oficiales definidos en el seeder
        $this->seed(\Database\Seeders\PermissionSeeder::class);

        $this->service = new RoleService();
    }

    /**
     * Verifica que se pueda crear un rol correctamente.
     */
    #[Test]
    public function createRoleSuccessfully()
    {
        $role = $this->service->createRole([
            'name' => 'Tester',
            'description' => 'Rol de pruebas',
            'permissions' => ['gestion_roles'],
        ]);

        $this->assertInstanceOf(Role::class, $role);
        $this->assertEquals('Tester', $role->name);
        $this->assertTrue($role->hasPermissionTo('gestion_roles'));
    }

    /**
     * Verifica que se pueda actualizar un rol correctamente.
     */
    #[Test]
    public function updateRoleSuccessfully()
    {
        $role = $this->service->createRole([
            'name' => 'Editor',
            'description' => 'Rol inicial',
            'permissions' => ['gestion_roles'],
        ]);

        $updatedRole = $this->service->updateRole($role, [
            'name' => 'Editor actualizado',
            'description' => 'Rol cambiado',
            'permissions' => ['gestion_usuarios'],
        ]);

        $this->assertEquals('Editor actualizado', $updatedRole->name);
        $this->assertTrue($updatedRole->hasPermissionTo('gestion_usuarios'));
    }

    /**
     * Verifica que se pueda eliminar un rol correctamente.
     */
    #[Test]
    public function deleteRoleSuccessfully()
    {
        $role = $this->service->createRole([
            'name' => 'Temporal',
            'description' => 'Se eliminará',
            'permissions' => ['gestion_roles'],
        ]);

        $this->service->deleteRole($role->id);

        $this->assertDatabaseMissing('roles', ['id' => $role->id]);
    }

    /**
     * Verifica que se puedan listar roles correctamente.
     */
    #[Test]
    public function listRolesSuccessfully()
    {
        $role = $this->service->createRole([
            'name' => 'Supervisor',
            'description' => 'Rol con gestión',
            'permissions' => ['gestion_roles'],
        ]);

        $roles = $this->service->listRoles();

        $this->assertCount(1, $roles);
        $this->assertEquals('Supervisor', $roles->first()->name);
        $this->assertTrue($roles->first()->hasPermissionTo('gestion_roles'));
    }

    /**
     * Verifica que se pueda obtener un rol por su ID.
     */
    #[Test]
    public function getRoleByIdSuccessfully()
    {
        $role = $this->service->createRole([
            'name' => 'Administrador',
            'description' => 'Rol de administrador',
            'permissions' => ['gestion_roles'],
        ]);

        $retrievedRole = $this->service->getRole($role->id);

        $this->assertNotNull($retrievedRole);
        $this->assertEquals('Administrador', $retrievedRole->name);
        $this->assertTrue($retrievedRole->hasPermissionTo('gestion_roles'));
    }

    /**
     * Verifica que se puedan listar permisos correctamente.
     */
    #[Test]
    public function listPermissionsSuccessfully()
    {
        $permissions = $this->service->listPermissions();

        // Validar que los permisos listados coincidan con los del config oficial
        $this->assertEqualsCanonicalizing(
            config('permissions.list'),
            $permissions->toArray()
        );
    }
}
