<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Process;

class ProcessFeatureTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_and_retrieve_a_process()
    {
        $accreditationCycle = \App\Models\AccreditationCycle::factory()->create();
        $process = Process::factory()->create([
            'ciclo_acreditacion_id' => $accreditationCycle->ciclo_acreditacion_id,
        ]);

        $found = Process::find($process->proceso_id);
        $this->assertNotNull($found);
        $this->assertEquals($process->proceso_id, $found->proceso_id);
    }
}
