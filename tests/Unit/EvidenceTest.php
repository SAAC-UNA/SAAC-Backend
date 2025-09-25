<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Evidence;

class EvidenceTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_creates_evidence()
    {
        $evidence = Evidence::factory()->create([
            'nombre' => 'Evidencia 1',
        ]);

        $this->assertDatabaseHas('EVIDENCIA', [
            'nombre' => 'Evidencia 1',
        ]);
    }
}
