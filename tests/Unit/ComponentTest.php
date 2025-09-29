<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Component;

class ComponentTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_creates_a_component()
    {
        // Prueba de creación de componente
        $dimension = \App\Models\Dimension::factory()->create();
        $component = \App\Models\Component::factory()->create([
            'dimension_id' => $dimension->dimension_id,
            'nombre' => 'Componente 1',
        ]);
        $this->assertDatabaseHas('COMPONENTE', [
            'nombre' => 'Componente 1',
            'dimension_id' => $dimension->dimension_id,
        ]);
    }

    /** @test */
    public function it_requires_nombre_field()
    {
        // Prueba de validación: campo nombre es obligatorio
        $dimension = \App\Models\Dimension::factory()->create();
        $this->expectException(\Illuminate\Database\QueryException::class);
        \App\Models\Component::factory()->create([
            'dimension_id' => $dimension->dimension_id,
            'nombre' => null,
        ]);
    }

    /** @test */
    public function it_updates_a_component()
    {
        // Prueba de actualización de componente
        $dimension = \App\Models\Dimension::factory()->create();
        $component = \App\Models\Component::factory()->create([
            'dimension_id' => $dimension->dimension_id,
            'nombre' => 'Original',
        ]);
        $component->update(['nombre' => 'Actualizado']);
        $this->assertDatabaseHas('COMPONENTE', ['nombre' => 'Actualizado']);
    }

    /** @test */
    public function it_deletes_a_component()
    {
        // Prueba de eliminación de componente
        $dimension = \App\Models\Dimension::factory()->create();
        $component = \App\Models\Component::factory()->create([
            'dimension_id' => $dimension->dimension_id,
        ]);
        $component->delete();
        $this->assertDatabaseMissing('COMPONENTE', ['componente_id' => $component->componente_id]);
    }

    /** @test */
    public function a_component_belongs_to_dimension()
    {
        // Prueba de relación belongsTo con Dimension
        $dimension = \App\Models\Dimension::factory()->create();
        $component = \App\Models\Component::factory()->create(['dimension_id' => $dimension->dimension_id]);
        $this->assertEquals($dimension->dimension_id, $component->dimension->dimension_id);
    }

    /** @test */
    public function a_component_has_many_criteria()
    {
        $dimension = \App\Models\Dimension::factory()->create();
        $component = \App\Models\Component::factory()->create([
            'dimension_id' => $dimension->dimension_id,
        ]);
        $criterion = \App\Models\Criterion::factory()->create([
            'componente_id' => $component->componente_id,
        ]);
        $component->refresh();
        $this->assertTrue($component->criteria->contains($criterion));
    }
}
