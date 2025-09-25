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
        $process = Process::factory()->create([
            'nombre' => 'Proceso 1',
        ]);

        $this->assertDatabaseHas('PROCESO', [
            'nombre' => 'Proceso 1',
        ]);
    }
}
