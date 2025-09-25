<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Criterion;

class CriterionFeatureTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_and_retrieve_a_criterion()
    {
        $dimension = \App\Models\Dimension::factory()->create();
        $component = \App\Models\Component::factory()->create([
            'dimension_id' => $dimension->dimension_id,
        ]);
        $criterion = Criterion::factory()->create([
            'componente_id' => $component->componente_id,
            'descripcion' => 'Criterio 2',
        ]);

        $found = Criterion::where('descripcion', 'Criterio 2')->where('componente_id', $component->componente_id)->first();
        $this->assertNotNull($found);
        $this->assertEquals('Criterio 2', $found->descripcion);
    }
}
