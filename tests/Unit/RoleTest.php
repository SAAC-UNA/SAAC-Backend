<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Role;

class RoleTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_creates_a_role()
    {
        // Prueba de creación de rol
        $role = \App\Models\Role::factory()->create([
            'name' => 'Administrador',
        ]);
        $this->assertDatabaseHas('roles', [
            'name' => 'Administrador',
        ]);
    }

    /** @test */
    public function it_requires_name_field()
    {
        // Prueba de validación: campo name es obligatorio
        $this->expectException(\Illuminate\Database\QueryException::class);
        \App\Models\Role::factory()->create(['name' => null]);
    }

    /** @test */
    public function it_updates_a_role()
    {
        // Prueba de actualización de rol
        $role = \App\Models\Role::factory()->create(['name' => 'Original']);
        $role->update(['name' => 'Actualizado']);
        $this->assertDatabaseHas('roles', ['name' => 'Actualizado']);
    }

    /** @test */
    public function it_deletes_a_role()
    {
        // Prueba de eliminación de rol
        $role = \App\Models\Role::factory()->create();
        $role->delete();
        $this->assertDatabaseMissing('roles', ['id' => $role->id]);
    }
}
