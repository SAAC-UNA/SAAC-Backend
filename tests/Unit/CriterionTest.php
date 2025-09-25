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
        $criterion = Criterion::factory()->create([
            'nombre' => 'Criterio 1',
        ]);

        $this->assertDatabaseHas('CRITERIO', [
            'nombre' => 'Criterio 1',
        ]);
    }
}
