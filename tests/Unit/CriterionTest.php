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
}
