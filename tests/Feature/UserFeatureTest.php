<?php

use App\Models\Role;
use App\Models\User;
use App\Services\LdapService;
use Laravel\Sanctum\Sanctum;

it('can create and retrieve a user', function () {
    $user = User::factory()->create([
        'nombre' => 'Maria Lopez',
        'cedula' => '987654321',
        'email' => 'maria@example.com',
    ]);

    $found = User::where('cedula', '987654321')->first();
    expect($found)->not->toBeNull();
    expect($found->nombre)->toBe('Maria Lopez');
    expect($found->email)->toBe('maria@example.com');
});

function userFeatureActingSuperuser(): User
{
    $user = User::factory()->create();
    $user->assignRole(Role::where('name', 'Superusuario')->first());
    Sanctum::actingAs($user);

    return $user;
}

beforeEach(function () {
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
});

it('superusuario can create a user from ldap with an initial role', function () {
    userFeatureActingSuperuser();

    $ldap = Mockery::mock(LdapService::class);
    $ldap->shouldReceive('getUserDataFromLdap')
        ->once()
        ->with('801490957')
        ->andReturn([
            'cedula' => '801490957',
            'nombre' => 'Usuario LDAP',
            'email' => 'usuario.ldap@una.cr',
        ]);
    app()->instance(LdapService::class, $ldap);

    $response = $this->postJson('/api/admin/users', [
        'cedula' => '801490957',
        'role' => 'Profesor',
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.cedula', '801490957')
        ->assertJsonPath('data.name', 'Usuario LDAP')
        ->assertJsonPath('data.email', 'usuario.ldap@una.cr');

    $created = User::where('cedula', '801490957')->first();
    expect($created)->not->toBeNull();
    expect($created->hasRole('Profesor'))->toBeTrue();
});

it('returns not found when ldap has no user for the cedula', function () {
    userFeatureActingSuperuser();

    $ldap = Mockery::mock(LdapService::class);
    $ldap->shouldReceive('getUserDataFromLdap')
        ->once()
        ->with('999999999')
        ->andReturn(null);
    app()->instance(LdapService::class, $ldap);

    $this->postJson('/api/admin/users', [
        'cedula' => '999999999',
        'role' => 'Profesor',
    ])->assertNotFound();
});

it('returns conflict when the user already exists locally', function () {
    userFeatureActingSuperuser();
    User::factory()->create(['cedula' => '801490957']);

    $this->postJson('/api/admin/users', [
        'cedula' => '801490957',
        'role' => 'Profesor',
    ])->assertStatus(409);
});

it('rejects user creation without usuarios create permission', function () {
    $user = User::factory()->create();
    $user->assignRole(Role::where('name', 'Profesor')->first());
    Sanctum::actingAs($user);

    $this->postJson('/api/admin/users', [
        'cedula' => '801490957',
        'role' => 'Profesor',
    ])->assertForbidden();
});
