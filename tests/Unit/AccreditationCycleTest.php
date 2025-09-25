<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\AccreditationCycle;

class AccreditationCycleTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_creates_an_accreditation_cycle()
    {
        $cycle = AccreditationCycle::factory()->create([
            'nombre' => 'Ciclo 2025',
        ]);

        $this->assertDatabaseHas('CICLO_ACREDITACION', [
            'nombre' => 'Ciclo 2025',
        ]);
    }
}
