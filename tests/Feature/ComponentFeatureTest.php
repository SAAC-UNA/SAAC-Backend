<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Component;

class ComponentFeatureTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_and_retrieve_a_component()
    {
        $component = Component::factory()->create([
            'nombre' => 'Componente 2',
        ]);

        $found = Component::where('nombre', 'Componente 2')->first();
        $this->assertNotNull($found);
        $this->assertEquals('Componente 2', $found->nombre);
    }
}
