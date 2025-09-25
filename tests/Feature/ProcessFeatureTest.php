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
        $process = Process::factory()->create([
            'nombre' => 'Proceso 2',
        ]);

        $found = Process::where('nombre', 'Proceso 2')->first();
        $this->assertNotNull($found);
        $this->assertEquals('Proceso 2', $found->nombre);
    }
}
