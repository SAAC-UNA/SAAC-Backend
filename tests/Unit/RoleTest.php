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
        $role = Role::factory()->create([
            'name' => 'Administrador',
        ]);

        $this->assertDatabaseHas('roles', [
            'name' => 'Administrador',
        ]);
    }
}
