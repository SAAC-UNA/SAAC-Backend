<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\EvidenceState;

class EvidenceStateTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_evidence_state_has_many_evidences()
    {
        $state = \App\Models\EvidenceState::factory()->create();
        $dimension = \App\Models\Dimension::factory()->create();
        $component = \App\Models\Component::factory()->create([
            'dimension_id' => $dimension->dimension_id,
        ]);
        $criterion = \App\Models\Criterion::factory()->create([
            'componente_id' => $component->componente_id,
        ]);
        $evidence = \App\Models\Evidence::factory()->create([
            'criterio_id' => $criterion->criterio_id,
            'estado_evidencia_id' => $state->estado_evidencia_id,
        ]);
        $state->refresh();
        $this->assertTrue($state->evidences->contains($evidence));
    }

    /** @test */
    public function it_creates_an_evidence_state()
    {
        $state = EvidenceState::factory()->create([
            'nombre' => 'Estado 1',
        ]);

        $this->assertDatabaseHas('ESTADO_EVIDENCIA', [
            'nombre' => 'Estado 1',
        ]);
    }

    /** @test */
    public function it_requires_nombre_field()
    {
        $this->expectException(\Illuminate\Database\QueryException::class);
        EvidenceState::factory()->create(['nombre' => null]);
    }

    /** @test */
    public function it_updates_an_evidence_state()
    {
        $state = EvidenceState::factory()->create(['nombre' => 'Original']);
        $state->update(['nombre' => 'Actualizado']);

        $this->assertDatabaseHas('ESTADO_EVIDENCIA', ['nombre' => 'Actualizado']);
    }

    /** @test */
    public function it_deletes_an_evidence_state()
    {
        $state = EvidenceState::factory()->create();
        $state->delete();

        $this->assertDatabaseMissing('ESTADO_EVIDENCIA', ['estado_evidencia_id' => $state->estado_evidencia_id]);
    }
}
