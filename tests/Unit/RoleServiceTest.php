<?php
// falta etenderle a esto esto era para ver si era asi , luego ver lode las alera para poder quitar el test luego lo mismompara el controller si es posi
//sible  DESDE CERO
namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
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
        $this->service = new RoleService();
    }

    #[Test]
    public function creaRolCorrectamente()
    {
        $permiso = Permission::create(['name' => 'gestion_roles', 'guard_name' => 'api']);

        $rol = $this->service->crearRol([
            'name' => 'Tester',
            'description' => 'Rol de pruebas',
            'permissions' => [$permiso->name],
        ]);

        $this->assertInstanceOf(Role::class, $rol);
        $this->assertEquals('Tester', $rol->name);
        $this->assertTrue($rol->hasPermissionTo('gestion_roles'));
    }

    #[Test]
    public function actualizaRolCorrectamente()
    {
        $permiso1 = Permission::create(['name' => 'gestion_roles', 'guard_name' => 'api']);
        $permiso2 = Permission::create(['name' => 'gestion_usuarios', 'guard_name' => 'api']);

        $rol = $this->service->crearRol([
            'name' => 'Editor',
            'description' => 'Rol inicial',
            'permissions' => [$permiso1->name],
        ]);

        $rolActualizado = $this->service->actualizarRol($rol, [
            'name' => 'Editor actualizado',
            'description' => 'Rol cambiado',
            'permissions' => [$permiso2->name],
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
            'permissions' => [],
        ]);

        $this->service->eliminarRol($rol->id);

        $this->assertDatabaseMissing('roles', ['id' => $rol->id]);
    }

    #[Test]
    public function listaRolesCorrectamente()
    {
        $permiso = Permission::create(['name' => 'gestion_roles', 'guard_name' => 'api']);
        $rol = $this->service->crearRol([
            'name' => 'Supervisor',
            'description' => 'Rol con gestión',
            'permissions' => [$permiso->name],
        ]);

        $roles = $this->service->listarRoles();

        $this->assertCount(1, $roles);
        $this->assertEquals('Supervisor', $roles->first()->name);
        $this->assertTrue($roles->first()->hasPermissionTo('gestion_roles'));
    }

    #[Test]
    public function obtieneRolPorIdCorrectamente()
    {
        $permiso = Permission::create(['name' => 'gestion_roles', 'guard_name' => 'api']);
        $rol = $this->service->crearRol([
            'name' => 'Administrador',
            'description' => 'Rol de administrador',
            'permissions' => [$permiso->name],
        ]);

        $rolObtenido = $this->service->obtenerRol($rol->id);

        $this->assertNotNull($rolObtenido);
        $this->assertEquals('Administrador', $rolObtenido->name);
        $this->assertTrue($rolObtenido->hasPermissionTo('gestion_roles'));
    }

    #[Test]
    public function listaPermisosCorrectamente()
    {
        Permission::create(['name' => 'gestion_roles', 'guard_name' => 'api']);
        Permission::create(['name' => 'gestion_usuarios', 'guard_name' => 'api']);

        $permisos = $this->service->listarPermisos();

        $this->assertCount(2, $permisos);
        $this->assertTrue($permisos->contains('gestion_roles'));
        $this->assertTrue($permisos->contains('gestion_usuarios'));
    }
}
