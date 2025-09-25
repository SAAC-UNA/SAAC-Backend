<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\EvidenceState;

class EvidenceStateTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_creates_an_evidence_state()
    {
        $state = EvidenceState::factory()->create([
            'nombre' => 'Estado 1',
        ]);

        $this->assertDatabaseHas('ESTADO_EVIDENCIA', [
            'nombre' => 'Estado 1',
        ]);
    }
}
