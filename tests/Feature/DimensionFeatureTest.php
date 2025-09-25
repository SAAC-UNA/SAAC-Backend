<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Dimension;

class DimensionFeatureTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_and_retrieve_a_dimension()
    {
        $dimension = Dimension::factory()->create([
            'nombre' => 'Dimensión 2',
        ]);

        $found = Dimension::where('nombre', 'Dimensión 2')->first();
        $this->assertNotNull($found);
        $this->assertEquals('Dimensión 2', $found->nombre);
    }
}
