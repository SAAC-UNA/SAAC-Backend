<?php

use App\Models\Role;
use App\Models\User;
use Spatie\Permission\Models\Permission;

it('creates a role', function () {
    $role = Role::factory()->create([
        'name' => 'Administrador',
        'guard_name' => 'api'
    ]);

    $this->assertDatabaseHas('roles', [
        'name' => 'Administrador',
        'guard_name' => 'api'
    ]);
});

it('requires name field', function () {
    Role::factory()->create(['name' => null]);
})->throws(\Illuminate\Database\QueryException::class);

it('updates a role', function () {
    $role = Role::factory()->create([
        'name' => 'Original',
        'guard_name' => 'api'
    ]);

    $role->update(['name' => 'Actualizado']);

    $this->assertDatabaseHas('roles', ['name' => 'Actualizado']);
});

it('deletes a role', function () {
    $role = Role::factory()->create(['guard_name' => 'api']);
    $roleId = $role->id;

    $role->delete();

    $this->assertDatabaseMissing('roles', ['id' => $roleId]);
});

it('role can have permissions', function () {
    $role = Role::factory()->create(['guard_name' => 'api']);

    $permission = Permission::create([
        'name' => 'test.permission',
        'guard_name' => 'api'
    ]);

    $role->givePermissionTo($permission);

    expect($role->hasPermissionTo('test.permission'))->toBeTrue();
});

it('role can be assigned to users', function () {
    $role = Role::factory()->create([
        'name' => 'Test Role',
        'guard_name' => 'api'
    ]);

    $user = User::factory()->create();
    $user->assignRole($role);

    expect($user->hasRole('Test Role'))->toBeTrue();
});

it('can retrieve role permissions', function () {
    $role = Role::factory()->create(['guard_name' => 'api']);

    $permission1 = Permission::create(['name' => 'read.data', 'guard_name' => 'api']);
    $permission2 = Permission::create(['name' => 'write.data', 'guard_name' => 'api']);

    $role->givePermissionTo([$permission1, $permission2]);

    $this->assertCount(2, $role->permissions);
    expect($role->permissions->contains('name', 'read.data'))->toBeTrue();
    expect($role->permissions->contains('name', 'write.data'))->toBeTrue();
});
