<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Role;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use PHPUnit\Framework\Attributes\Test;

class RoleTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_creates_a_role()
    {
        $role = Role::factory()->create([
            'name' => 'Administrador',
            'guard_name' => 'api'
        ]);

        $this->assertDatabaseHas('roles', [
            'name' => 'Administrador',
            'guard_name' => 'api'
        ]);
    }

    #[Test]
    public function it_requires_name_field()
    {
        $this->expectException(\Illuminate\Database\QueryException::class);
        Role::factory()->create(['name' => null]);
    }

    #[Test]
    public function it_updates_a_role()
    {
        $role = Role::factory()->create([
            'name' => 'Original',
            'guard_name' => 'api'
        ]);

        $role->update(['name' => 'Actualizado']);

        $this->assertDatabaseHas('roles', ['name' => 'Actualizado']);
    }

    #[Test]
    public function it_deletes_a_role()
    {
        $role = Role::factory()->create(['guard_name' => 'api']);
        $roleId = $role->id;

        $role->delete();

        $this->assertDatabaseMissing('roles', ['id' => $roleId]);
    }

    #[Test]
    public function a_role_can_have_permissions()
    {
        $role = Role::factory()->create(['guard_name' => 'api']);

        $permission = Permission::create([
            'name' => 'test.permission',
            'guard_name' => 'api'
        ]);

        $role->givePermissionTo($permission);

        $this->assertTrue($role->hasPermissionTo('test.permission'));
    }

    #[Test]
    public function a_role_can_be_assigned_to_users()
    {
        $role = Role::factory()->create([
            'name' => 'Test Role',
            'guard_name' => 'api'
        ]);

        $user = User::factory()->create();
        $user->assignRole($role);

        $this->assertTrue($user->hasRole('Test Role'));
    }

    #[Test]
    public function it_can_retrieve_role_permissions()
    {
        $role = Role::factory()->create(['guard_name' => 'api']);

        $permission1 = Permission::create(['name' => 'read.data', 'guard_name' => 'api']);
        $permission2 = Permission::create(['name' => 'write.data', 'guard_name' => 'api']);

        $role->givePermissionTo([$permission1, $permission2]);

        $this->assertCount(2, $role->permissions);
        $this->assertTrue($role->permissions->contains('name', 'read.data'));
        $this->assertTrue($role->permissions->contains('name', 'write.data'));
    }
}
