<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Criterion;

class CriterionFeatureTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_and_retrieve_a_criterion()
    {
        $criterion = Criterion::factory()->create([
            'nombre' => 'Criterio 2',
        ]);

        $found = Criterion::where('nombre', 'Criterio 2')->first();
        $this->assertNotNull($found);
        $this->assertEquals('Criterio 2', $found->nombre);
    }
}
