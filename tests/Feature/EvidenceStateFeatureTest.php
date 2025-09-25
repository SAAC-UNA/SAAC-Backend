<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\EvidenceState;

class EvidenceStateFeatureTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_and_retrieve_an_evidence_state()
    {
        $state = EvidenceState::factory()->create([
            'nombre' => 'Estado 2',
        ]);

        $found = EvidenceState::where('nombre', 'Estado 2')->first();
        $this->assertNotNull($found);
        $this->assertEquals('Estado 2', $found->nombre);
    }
}
