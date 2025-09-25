<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Evidence;

class EvidenceFeatureTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_and_retrieve_evidence()
    {
        $evidence = Evidence::factory()->create([
            'nombre' => 'Evidencia 2',
        ]);

        $found = Evidence::where('nombre', 'Evidencia 2')->first();
        $this->assertNotNull($found);
        $this->assertEquals('Evidencia 2', $found->nombre);
    }
}
