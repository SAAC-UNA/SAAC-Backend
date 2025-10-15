<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\AccreditationCycle;

class AccreditationCycleFeatureTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_and_retrieve_an_accreditation_cycle()
    {
        $cycle = AccreditationCycle::factory()->create([
            'nombre' => 'Ciclo 2026',
        ]);

        $found = AccreditationCycle::where('nombre', 'Ciclo 2026')->first();
        $this->assertNotNull($found);
        $this->assertEquals('Ciclo 2026', $found->nombre);
    }
}

