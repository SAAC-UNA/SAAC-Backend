<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\ImprovementCommitment;

class ImprovementCommitmentTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_creates_an_improvement_commitment()
    {
        $commitment = ImprovementCommitment::factory()->create([
            'nombre' => 'Compromiso 1',
        ]);

        $this->assertDatabaseHas('COMPROMISO_MEJORA', [
            'nombre' => 'Compromiso 1',
        ]);
    }
}
