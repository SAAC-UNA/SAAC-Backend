<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Criterion;

class CriterionTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_creates_a_criterion()
    {
        // Prueba de creación de criterio
        $component = \App\Models\Component::factory()->create([
            'dimension_id' => \App\Models\Dimension::factory()->create()->dimension_id,
        ]);
        $criterion = \App\Models\Criterion::factory()->create([
            'componente_id' => $component->componente_id,
            'descripcion' => 'Criterio 1',
        ]);
        $this->assertDatabaseHas('CRITERIO', [
            'descripcion' => 'Criterio 1',
            'componente_id' => $component->componente_id,
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
        $this->expectException(\Illuminate\Database\QueryException::class);
        \App\Models\Criterion::factory()->create([
            'componente_id' => $component->componente_id,
            'descripcion' => null,
        ]);
    }

    /** @test */
    public function it_updates_a_criterion()
    {
        // Prueba de actualización de criterio
        $dimension = \App\Models\Dimension::factory()->create();
        $component = \App\Models\Component::factory()->create([
            'dimension_id' => $dimension->dimension_id,
        ]);
        $criterion = \App\Models\Criterion::factory()->create([
            'componente_id' => $component->componente_id,
            'descripcion' => 'Original',
        ]);
        $criterion->update(['descripcion' => 'Actualizado']);
        $this->assertDatabaseHas('CRITERIO', ['descripcion' => 'Actualizado']);
    }

    /** @test */
    public function it_deletes_a_criterion()
    {
        // Prueba de eliminación de criterio
        $dimension = \App\Models\Dimension::factory()->create();
        $component = \App\Models\Component::factory()->create([
            'dimension_id' => $dimension->dimension_id,
        ]);
        $criterion = \App\Models\Criterion::factory()->create([
            'componente_id' => $component->componente_id,
        ]);
        $criterion->delete();
        $this->assertDatabaseMissing('CRITERIO', ['criterio_id' => $criterion->criterio_id]);
    }

    /** @test */
    public function a_criterion_belongs_to_component()
    {
        // Prueba de relación belongsTo con Component
        $dimension = \App\Models\Dimension::factory()->create();
        $component = \App\Models\Component::factory()->create([
            'dimension_id' => $dimension->dimension_id,
        ]);
        $criterion = \App\Models\Criterion::factory()->create(['componente_id' => $component->componente_id]);
        $this->assertEquals($component->componente_id, $criterion->component->componente_id);
    }

    /** @test */
    public function a_criterion_has_many_evidences()
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
        $criterion->refresh();
        $this->assertTrue($criterion->evidences->contains($evidence));
    }
}
