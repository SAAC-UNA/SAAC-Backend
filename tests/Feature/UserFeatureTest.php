<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;

class UserFeatureTest extends TestCase
{
    use RefreshDatabase;

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
