<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Role;

class RoleFeatureTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_and_retrieve_a_role()
    {
        $role = Role::factory()->create([
            'name' => 'Usuario',
        ]);

        $found = Role::where('name', 'Usuario')->first();
        $this->assertNotNull($found);
        $this->assertEquals('Usuario', $found->name);
    }
}
