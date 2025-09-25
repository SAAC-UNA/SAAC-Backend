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
}
