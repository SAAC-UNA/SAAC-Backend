<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Dimension;

class DimensionTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_creates_a_dimension()
    {
        // Prueba de creación de dimensión
        $comment = \App\Models\Comment::factory()->create();
        $dimension = \App\Models\Dimension::factory()->create([
            'comentario_id' => $comment->comentario_id,
            'nombre' => 'Dimensión 1',
        ]);
        $this->assertDatabaseHas('DIMENSION', [
            'nombre' => 'Dimensión 1',
            'comentario_id' => $comment->comentario_id,
        ]);
    }

    /** @test */
    public function it_requires_nombre_field()
    {
        // Prueba de validación: campo nombre es obligatorio
        $comment = \App\Models\Comment::factory()->create();
        $this->expectException(\Illuminate\Database\QueryException::class);
        \App\Models\Dimension::factory()->create([
            'comentario_id' => $comment->comentario_id,
            'nombre' => null,
        ]);
    }

    /** @test */
    public function it_updates_a_dimension()
    {
        // Prueba de actualización de dimensión
        $comment = \App\Models\Comment::factory()->create();
        $dimension = \App\Models\Dimension::factory()->create([
            'comentario_id' => $comment->comentario_id,
            'nombre' => 'Original',
        ]);
        $dimension->update(['nombre' => 'Actualizado']);
        $this->assertDatabaseHas('DIMENSION', ['nombre' => 'Actualizado']);
    }

    /** @test */
    public function it_deletes_a_dimension()
    {
        // Prueba de eliminación de dimensión
        $comment = \App\Models\Comment::factory()->create();
        $dimension = \App\Models\Dimension::factory()->create([
            'comentario_id' => $comment->comentario_id,
        ]);
        $dimension->delete();
        $this->assertDatabaseMissing('DIMENSION', ['dimension_id' => $dimension->dimension_id]);
    }

    /** @test */
    public function a_dimension_has_many_components()
    {
        // Prueba de relación hasMany con Component
        $dimension = \App\Models\Dimension::factory()->create();
        $component = \App\Models\Component::factory()->create(['dimension_id' => $dimension->dimension_id]);
        $this->assertTrue($dimension->components->contains($component));
    }
}
