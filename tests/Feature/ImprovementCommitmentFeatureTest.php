<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\ImprovementCommitment;

class ImprovementCommitmentFeatureTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_and_retrieve_an_improvement_commitment()
    {
        $commitment = ImprovementCommitment::factory()->create([
            'nombre' => 'Compromiso 2',
        ]);

        $found = ImprovementCommitment::where('nombre', 'Compromiso 2')->first();
        $this->assertNotNull($found);
        $this->assertEquals('Compromiso 2', $found->nombre);
    }
}
