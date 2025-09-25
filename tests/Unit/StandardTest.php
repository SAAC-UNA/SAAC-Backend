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
}
