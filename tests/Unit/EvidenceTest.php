<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Evidence;

class EvidenceTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_creates_evidence()
    {
        $criterion = \App\Models\Criterion::factory()->create([
            'componente_id' => \App\Models\Component::factory()->create([
                'dimension_id' => \App\Models\Dimension::factory()->create()->dimension_id,
            ])->componente_id,
        ]);
        $evidenceState = \App\Models\EvidenceState::factory()->create();
        $evidence = \App\Models\Evidence::factory()->create([
            'criterio_id' => $criterion->criterio_id,
            'estado_evidencia_id' => $evidenceState->estado_evidencia_id,
            'descripcion' => 'Evidencia 1',
        ]);

        $this->assertDatabaseHas('EVIDENCIA', [
            'descripcion' => 'Evidencia 1',
            'criterio_id' => $criterion->criterio_id,
            'estado_evidencia_id' => $evidenceState->estado_evidencia_id,
        ]);
    }
}
