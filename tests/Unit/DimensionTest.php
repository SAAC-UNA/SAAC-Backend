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
        $dimension = Dimension::factory()->create([
            'nombre' => 'Dimensión 1',
        ]);

        $this->assertDatabaseHas('DIMENSION', [
            'nombre' => 'Dimensión 1',
        ]);
    }
}
