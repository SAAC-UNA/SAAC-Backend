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
        $commitment = ImprovementCommitment::factory()->create();

        $found = ImprovementCommitment::find($commitment->compromiso_mejora_id);
        $this->assertNotNull($found);
        $this->assertEquals($commitment->compromiso_mejora_id, $found->compromiso_mejora_id);
    }
}

