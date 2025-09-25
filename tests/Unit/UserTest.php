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
}
