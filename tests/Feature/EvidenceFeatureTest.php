<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Evidence;

class EvidenceFeatureTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_and_retrieve_evidence()
    {
        $dimension = \App\Models\Dimension::factory()->create();
        $component = \App\Models\Component::factory()->create([
            'dimension_id' => $dimension->dimension_id,
        ]);
        $criterion = \App\Models\Criterion::factory()->create([
            'componente_id' => $component->componente_id,
        ]);
        $evidenceState = \App\Models\EvidenceState::factory()->create();
        $evidence = Evidence::factory()->create([
            'criterio_id' => $criterion->criterio_id,
            'estado_evidencia_id' => $evidenceState->estado_evidencia_id,
            'descripcion' => 'Evidencia 2',
        ]);

        $found = Evidence::where('descripcion', 'Evidencia 2')->where('criterio_id', $criterion->criterio_id)->first();
        $this->assertNotNull($found);
        $this->assertEquals('Evidencia 2', $found->descripcion);
    }
}
