<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Standard;

class StandardTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_creates_a_standard()
    {
        // Prueba de creación de estándar
        $criterion = \App\Models\Criterion::factory()->create([
            'componente_id' => \App\Models\Component::factory()->create([
                'dimension_id' => \App\Models\Dimension::factory()->create()->dimension_id,
            ])->componente_id,
        ]);
        $standard = \App\Models\Standard::factory()->create([
            'criterio_id' => $criterion->criterio_id,
            'descripcion' => 'Estándar 1',
        ]);
        $this->assertDatabaseHas('ESTANDAR', [
            'descripcion' => 'Estándar 1',
            'criterio_id' => $criterion->criterio_id,
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
        $this->expectException(\Illuminate\Database\QueryException::class);
        \App\Models\Standard::factory()->create([
            'criterio_id' => $criterion->criterio_id,
            'descripcion' => null,
        ]);
    }

    /** @test */
    public function it_updates_a_standard()
    {
        // Prueba de actualización de estándar
        $dimension = \App\Models\Dimension::factory()->create();
        $component = \App\Models\Component::factory()->create([
            'dimension_id' => $dimension->dimension_id,
        ]);
        $criterion = \App\Models\Criterion::factory()->create([
            'componente_id' => $component->componente_id,
        ]);
        $standard = \App\Models\Standard::factory()->create([
            'criterio_id' => $criterion->criterio_id,
            'descripcion' => 'Original',
        ]);
        $standard->update(['descripcion' => 'Actualizado']);
        $this->assertDatabaseHas('ESTANDAR', ['descripcion' => 'Actualizado']);
    }

    /** @test */
    public function it_deletes_a_standard()
    {
        // Prueba de eliminación de estándar
        $dimension = \App\Models\Dimension::factory()->create();
        $component = \App\Models\Component::factory()->create([
            'dimension_id' => $dimension->dimension_id,
        ]);
        $criterion = \App\Models\Criterion::factory()->create([
            'componente_id' => $component->componente_id,
        ]);
        $standard = \App\Models\Standard::factory()->create([
            'criterio_id' => $criterion->criterio_id,
        ]);
        $standard->delete();
        $this->assertDatabaseMissing('ESTANDAR', ['estandar_id' => $standard->estandar_id]);
    }

    /** @test */
    public function a_standard_belongs_to_criterion()
    {
        // Prueba de relación belongsTo con Criterion
        $dimension = \App\Models\Dimension::factory()->create();
        $component = \App\Models\Component::factory()->create([
            'dimension_id' => $dimension->dimension_id,
        ]);
        $criterion = \App\Models\Criterion::factory()->create([
            'componente_id' => $component->componente_id,
        ]);
        $standard = \App\Models\Standard::factory()->create(['criterio_id' => $criterion->criterio_id]);
        $standard->refresh();
        $this->assertEquals($criterion->criterio_id, $standard->criterion->criterio_id);
    }
}
