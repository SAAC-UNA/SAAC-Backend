<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Process;

class ProcessTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_creates_a_process()
    {
        $accreditationCycle = \App\Models\AccreditationCycle::factory()->create();
        $process = \App\Models\Process::factory()->create([
            'ciclo_acreditacion_id' => $accreditationCycle->ciclo_acreditacion_id,
        ]);

        $this->assertDatabaseHas('PROCESO', [
            'ciclo_acreditacion_id' => $accreditationCycle->ciclo_acreditacion_id,
            'proceso_id' => $process->proceso_id,
        ]);
    }
}
