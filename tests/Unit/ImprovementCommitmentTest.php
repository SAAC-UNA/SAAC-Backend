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
        $commitment = \App\Models\ImprovementCommitment::factory()->create();

        $this->assertDatabaseHas('COMPROMISO_MEJORA', [
            'compromiso_mejora_id' => $commitment->compromiso_mejora_id,
        ]);
    }
}
