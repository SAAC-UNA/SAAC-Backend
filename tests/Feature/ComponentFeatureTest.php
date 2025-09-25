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
        $dimension = \App\Models\Dimension::factory()->create();
        $component = Component::factory()->create([
            'dimension_id' => $dimension->dimension_id,
            'nombre' => 'Componente 2',
        ]);

        $found = Component::where('nombre', 'Componente 2')->where('dimension_id', $dimension->dimension_id)->first();
        $this->assertNotNull($found);
        $this->assertEquals('Componente 2', $found->nombre);
    }
}
