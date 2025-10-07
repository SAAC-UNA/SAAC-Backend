<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;

class UserRoleFeatureTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function puede_asignar_un_rol_a_un_usuario()
    {
        // Dado: usuario y rol existente con guard 'api'
        $user = User::factory()->create();

        Role::firstOrCreate([
            'name'       => 'Administrador',
            'guard_name' => 'api',       // <- importante para tu validador
        ]);

        $payload = ['role' => 'Administrador'];

        // Cuando
        $response = $this->putJson("/api/admin/users/{$user->getKey()}/role", $payload);

        // Entonces
        $response->assertStatus(200)
                 ->assertJson([
                     'message' => 'Rol asignado correctamente',
                     'role'    => 'Administrador',
                 ]);

        // (Opcional, si ya integraste spatie en User)
        // $this->assertTrue($user->fresh()->hasRole('Administrador'));
    }

    #[Test]
    public function puede_asignar_permisos_a_un_usuario()
    {
        $user = User::factory()->create();

        // Estructura esperada por tu AssignPermissionsRequest: arrays de STRINGS
        $modules = [
            'evidencias' => ['ver', 'crear'],   // sin booleans
            'reportes'   => ['generar'],
        ];

        $response = $this->putJson("/api/admin/users/{$user->getKey()}/permissions", [
            'modules' => $modules,
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'message' => 'Permisos actualizados correctamente',
                 ])
                 ->assertJsonStructure([
                     'message',
                     'user_id',
                     'granted',
                 ]);

        // (Opcional) Si tu service realmente asigna permisos con Spatie,
        // podrías verificar alguno concreto, p.ej.:
        // $this->assertTrue($user->fresh()->can('evidencias.ver'));
    }
}
