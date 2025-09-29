<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;

class UserTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_creates_a_user()
    {
        // Prueba de creación de usuario
        $user = User::factory()->create([
            'nombre' => 'Juan Perez',
            'cedula' => '123456789',
            'email' => 'juan@example.com',
        ]);
        $this->assertDatabaseHas('USUARIO', [
            'nombre' => 'Juan Perez',
            'cedula' => '123456789',
            'email' => 'juan@example.com',
        ]);
    }

    /** @test */
    public function it_requires_nombre_field()
    {
        // Prueba de validación: campo nombre es obligatorio
        $this->expectException(\Illuminate\Database\QueryException::class);
        User::factory()->create(['nombre' => null]);
    }

    /** @test */
    public function it_requires_email_field()
    {
        // Prueba de validación: campo email es obligatorio
        $this->expectException(\Illuminate\Database\QueryException::class);
        User::factory()->create(['email' => null]);
    }

    /** @test */
    public function it_updates_a_user()
    {
        // Prueba de actualización de usuario
        $user = User::factory()->create(['nombre' => 'Original']);
        $user->update(['nombre' => 'Actualizado']);
        $this->assertDatabaseHas('USUARIO', ['nombre' => 'Actualizado']);
    }

    /** @test */
    public function it_deletes_a_user()
    {
        // Prueba de eliminación de usuario
        $user = User::factory()->create();
        $user->delete();
        $this->assertDatabaseMissing('USUARIO', ['usuario_id' => $user->usuario_id]);
    }
}
