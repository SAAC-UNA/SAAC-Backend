<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Campus;

class CampusFeatureTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_and_retrieve_a_campus()
    {
        $campus = Campus::factory()->create([
            'nombre' => 'Campus Norte',
        ]);

        $found = Campus::where('nombre', 'Campus Norte')->first();
        $this->assertNotNull($found);
        $this->assertEquals('Campus Norte', $found->nombre);
    }
}

