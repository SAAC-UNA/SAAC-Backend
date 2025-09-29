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
        // Prueba de creación de evidencia
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

    /** @test */
    public function it_requires_descripcion_field()
    {
        // Prueba de validación: campo descripción es obligatorio
        $dimension = \App\Models\Dimension::factory()->create();
        $component = \App\Models\Component::factory()->create([
            'dimension_id' => $dimension->dimension_id,
        ]);
        $criterion = \App\Models\Criterion::factory()->create([
            'componente_id' => $component->componente_id,
        ]);
        $evidenceState = \App\Models\EvidenceState::factory()->create();
        $this->expectException(\Illuminate\Database\QueryException::class);
        \App\Models\Evidence::factory()->create([
            'criterio_id' => $criterion->criterio_id,
            'estado_evidencia_id' => $evidenceState->estado_evidencia_id,
            'descripcion' => null,
        ]);
    }

    /** @test */
    public function it_updates_evidence()
    {
        // Prueba de actualización de evidencia
        $dimension = \App\Models\Dimension::factory()->create();
        $component = \App\Models\Component::factory()->create([
            'dimension_id' => $dimension->dimension_id,
        ]);
        $criterion = \App\Models\Criterion::factory()->create([
            'componente_id' => $component->componente_id,
        ]);
        $evidenceState = \App\Models\EvidenceState::factory()->create();
        $evidence = \App\Models\Evidence::factory()->create([
            'criterio_id' => $criterion->criterio_id,
            'estado_evidencia_id' => $evidenceState->estado_evidencia_id,
            'descripcion' => 'Original',
        ]);
        $evidence->update(['descripcion' => 'Actualizado']);
        $this->assertDatabaseHas('EVIDENCIA', ['descripcion' => 'Actualizado']);
    }

    /** @test */
    public function it_deletes_evidence()
    {
        // Prueba de eliminación de evidencia
        $dimension = \App\Models\Dimension::factory()->create();
        $component = \App\Models\Component::factory()->create([
            'dimension_id' => $dimension->dimension_id,
        ]);
        $criterion = \App\Models\Criterion::factory()->create([
            'componente_id' => $component->componente_id,
        ]);
        $evidenceState = \App\Models\EvidenceState::factory()->create();
        $evidence = \App\Models\Evidence::factory()->create([
            'criterio_id' => $criterion->criterio_id,
            'estado_evidencia_id' => $evidenceState->estado_evidencia_id,
        ]);
        $evidence->delete();
        $this->assertDatabaseMissing('EVIDENCIA', ['evidencia_id' => $evidence->evidencia_id]);
    }

    /** @test */
    public function evidence_belongs_to_criterion()
    {
        $dimension = \App\Models\Dimension::factory()->create();
        $component = \App\Models\Component::factory()->create([
            'dimension_id' => $dimension->dimension_id,
        ]);
        $criterion = \App\Models\Criterion::factory()->create([
            'componente_id' => $component->componente_id,
        ]);
        $evidence = \App\Models\Evidence::factory()->create([
            'criterio_id' => $criterion->criterio_id,
        ]);
        $this->assertEquals($criterion->criterio_id, $evidence->criterion->criterio_id);
    }
}
