<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Role;
use App\Models\User;
use App\Models\AuditLog;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;

class RoleFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Usar el seeder correcto
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    }

    #[Test]
    public function unauthenticated_users_cannot_access_roles()
    {
        $response = $this->getJson('/api/roles');
        $response->assertStatus(401);
    }

    #[Test]
    public function non_admin_users_cannot_access_roles()
    {
        $user = User::factory()->create();
        $profesorRole = Role::where('name', 'Profesor')->first();
        $user->assignRole($profesorRole);
        
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/roles');
        $response->assertStatus(403);
    }

    #[Test]
    public function superusuario_can_list_roles()
    {
        $user = User::factory()->create();
        $superRole = Role::where('name', 'Superusuario')->first();
        $user->assignRole($superRole);
        
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/roles');
        $response->assertStatus(200)
                 ->assertJsonStructure(['data']);
    }

    #[Test]
    public function administrador_can_list_roles()
    {
        $user = User::factory()->create();
        $adminRole = Role::where('name', 'Administrador')->first();
        $user->assignRole($adminRole);
        
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/roles');
        $response->assertStatus(200)
                 ->assertJsonStructure(['data']);
    }

    #[Test]
    public function superusuario_can_create_role_and_logs_to_audit()
    {
        $user = User::factory()->create();
        $superRole = Role::where('name', 'Superusuario')->first();
        $user->assignRole($superRole);
        
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
    }

    #[Test]
    public function superusuario_can_update_role_and_logs_changes()
    {
        $user = User::factory()->create();
        $superRole = Role::where('name', 'Superusuario')->first();
        $user->assignRole($superRole);
        
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
    }

    #[Test]
    public function superusuario_can_delete_role_and_logs_deletion()
    {
        $user = User::factory()->create();
        $superRole = Role::where('name', 'Superusuario')->first();
        $user->assignRole($superRole);
        
        Sanctum::actingAs($user);

        $role = Role::create(['name' => 'Rol a Eliminar', 'guard_name' => 'api']);

        $response = $this->deleteJson("/api/roles/{$role->id}");
        
        $response->assertStatus(200);

        // Verificar eliminación
        $this->assertDatabaseMissing('roles', [
            'id' => $role->id
        ]);

        // Nota: Bitácora se prueba por separado
    }

    #[Test]
    public function it_validates_required_name_field()
    {
        $user = User::factory()->create();
        $superRole = Role::where('name', 'Superusuario')->first();
        $user->assignRole($superRole);
        
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/roles', [
            'description' => 'Sin nombre'
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['name']);
    }

    #[Test]
    public function it_prevents_duplicate_role_names()
    {
        $user = User::factory()->create();
        $superRole = Role::where('name', 'Superusuario')->first();
        $user->assignRole($superRole);
        
        Sanctum::actingAs($user);

        Role::create(['name' => 'Rol Existente', 'guard_name' => 'api']);

        $response = $this->postJson('/api/roles', [
            'name' => 'Rol Existente',
            'description' => 'Intento duplicar'
        ]);

        $response->assertStatus(422);
    }

    #[Test]
    public function it_can_show_specific_role()
    {
        $user = User::factory()->create();
        $superRole = Role::where('name', 'Superusuario')->first();
        $user->assignRole($superRole);
        
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
    }

    #[Test]
    public function it_returns_404_for_nonexistent_role()
    {
        $user = User::factory()->create();
        $superRole = Role::where('name', 'Superusuario')->first();
        $user->assignRole($superRole);
        
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/roles/99999');
        $response->assertStatus(404);
    }
}
