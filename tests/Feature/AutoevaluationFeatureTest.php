<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Autoevaluation;

class AutoevaluationFeatureTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_and_retrieve_an_autoevaluation()
    {
        $autoevaluation = Autoevaluation::factory()->create();

        $found = Autoevaluation::find($autoevaluation->autoevaluacion_id);
        $this->assertNotNull($found);
        $this->assertEquals($autoevaluation->autoevaluacion_id, $found->autoevaluacion_id);
    }
}
