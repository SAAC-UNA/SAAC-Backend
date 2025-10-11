<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;

class UserFeatureTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function activa_un_usuario_inactivo()
    {
        // Dado un usuario INACTIVO
        $user = User::factory()->create([
            'status' => User::STATUS_INACTIVE, // 'inactive'
        ]);

        // Cuando llamo al endpoint de activar (sin auth ni mocks)
        $res = $this->patch("/api/admin/users/{$user->getKey()}/activate");

        // Entonces responde 200 y cambia el estado
        $res->assertStatus(200)
            ->assertJson(['message' => 'Usuario activado']);

        $this->assertEquals(User::STATUS_ACTIVE, $user->fresh()->status);
    }

    /** @test */
    public function no_activa_si_ya_esta_activo()
    {
        $user = User::factory()->create([
            'status' => User::STATUS_ACTIVE, // 'active'
        ]);

        $res = $this->patch("/api/admin/users/{$user->getKey()}/activate");

        $res->assertStatus(409)
            ->assertJson(['message' => 'El usuario ya estaba activo']);

        $this->assertEquals(User::STATUS_ACTIVE, $user->fresh()->status);
    }
    /** @test */
    public function desactiva_un_usuario_activo()
    {
        $user = User::factory()->create([
            'status' => User::STATUS_ACTIVE, // 'active'
        ]);

        $res = $this->patch("/api/admin/users/{$user->getKey()}/deactivate");

        $res->assertStatus(200)
            ->assertJson(['message' => 'Usuario desactivado']);

        $this->assertEquals(User::STATUS_INACTIVE, $user->fresh()->status);
    }

    /** @test */
    public function no_desactiva_si_ya_esta_inactivo()
    {
        $user = User::factory()->create([
            'status' => User::STATUS_INACTIVE, // 'inactive'
        ]);

        $res = $this->patch("/api/admin/users/{$user->getKey()}/deactivate");

        $res->assertStatus(409)
            ->assertJson(['message' => 'El usuario ya estaba inactivo']);

        $this->assertEquals(User::STATUS_INACTIVE, $user->fresh()->status);
    }


    /** @test */
    public function it_can_create_and_retrieve_a_user()
    {
        $user = User::factory()->create([
            'nombre' => 'Maria Lopez',
            'cedula' => '987654321',
            'email' => 'maria@example.com',
        ]);

        $found = User::where('cedula', '987654321')->first();
        $this->assertNotNull($found);
        $this->assertEquals('Maria Lopez', $found->nombre);
        $this->assertEquals('maria@example.com', $found->email);
    }
}
