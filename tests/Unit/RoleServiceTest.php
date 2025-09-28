<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Role;
use App\Services\RoleService;
use PHPUnit\Framework\Attributes\Test;

class RoleServiceTest extends TestCase
{
    use RefreshDatabase;

    private RoleService $service;

    protected function setUp(): void
    {
        parent::setUp();

        // Cargar los permisos oficiales desde el seeder
        $this->seed(\Database\Seeders\PermissionSeeder::class);

        $this->service = new RoleService();
    }

    #[Test]
    public function creaRolCorrectamente()
    {
        $rol = $this->service->crearRol([
            'name' => 'Tester',
            'description' => 'Rol de pruebas',
            'permissions' => ['gestion_roles'],
        ]);

        $this->assertInstanceOf(Role::class, $rol);
        $this->assertEquals('Tester', $rol->name);
        $this->assertTrue($rol->hasPermissionTo('gestion_roles'));
    }

    #[Test]
    public function actualizaRolCorrectamente()
    {
        $rol = $this->service->crearRol([
            'name' => 'Editor',
            'description' => 'Rol inicial',
            'permissions' => ['gestion_roles'],
        ]);

        $rolActualizado = $this->service->actualizarRol($rol, [
            'name' => 'Editor actualizado',
            'description' => 'Rol cambiado',
            'permissions' => ['gestion_usuarios'],
        ]);

        $this->assertEquals('Editor actualizado', $rolActualizado->name);
        $this->assertTrue($rolActualizado->hasPermissionTo('gestion_usuarios'));
    }

    #[Test]
    public function eliminaRolCorrectamente()
    {
        $rol = $this->service->crearRol([
            'name' => 'Temporal',
            'description' => 'Se eliminará',
            'permissions' => ['gestion_roles'],
        ]);

        $this->service->eliminarRol($rol->id);

        $this->assertDatabaseMissing('roles', ['id' => $rol->id]);
    }

    #[Test]
    public function listaRolesCorrectamente()
    {
        $rol = $this->service->crearRol([
            'name' => 'Supervisor',
            'description' => 'Rol con gestión',
            'permissions' => ['gestion_roles'],
        ]);

        $roles = $this->service->listarRoles();

        $this->assertCount(1, $roles);
        $this->assertEquals('Supervisor', $roles->first()->name);
        $this->assertTrue($roles->first()->hasPermissionTo('gestion_roles'));
    }

    #[Test]
    public function obtieneRolPorIdCorrectamente()
    {
        $rol = $this->service->crearRol([
            'name' => 'Administrador',
            'description' => 'Rol de administrador',
            'permissions' => ['gestion_roles'],
        ]);

        $rolObtenido = $this->service->obtenerRol($rol->id);

        $this->assertNotNull($rolObtenido);
        $this->assertEquals('Administrador', $rolObtenido->name);
        $this->assertTrue($rolObtenido->hasPermissionTo('gestion_roles'));
    }

    #[Test]
    public function listaPermisosCorrectamente()
    {
        $permisos = $this->service->listarPermisos();

        // Validar que los permisos listados coincidan con los del config oficial
        $this->assertEqualsCanonicalizing(
            config('permissions.list'),
            $permisos->toArray()
        );
    }
}
