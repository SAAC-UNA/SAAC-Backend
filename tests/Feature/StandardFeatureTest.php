<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Standard;

class StandardFeatureTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_and_retrieve_a_standard()
    {
        $dimension = \App\Models\Dimension::factory()->create();
        $component = \App\Models\Component::factory()->create([
            'dimension_id' => $dimension->dimension_id,
        ]);
        $criterion = \App\Models\Criterion::factory()->create([
            'componente_id' => $component->componente_id,
        ]);
        $standard = Standard::factory()->create([
            'criterio_id' => $criterion->criterio_id,
            'descripcion' => 'Estándar 2',
        ]);

        $found = Standard::where('descripcion', 'Estándar 2')->where('criterio_id', $criterion->criterio_id)->first();
        $this->assertNotNull($found);
        $this->assertEquals('Estándar 2', $found->descripcion);
    }
}
