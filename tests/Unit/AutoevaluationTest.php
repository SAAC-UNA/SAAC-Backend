<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Autoevaluation;

class AutoevaluationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_creates_an_autoevaluation()
    {
        $autoevaluation = \App\Models\Autoevaluation::factory()->create();

        $this->assertDatabaseHas('AUTOEVALUACION', [
            'autoevaluacion_id' => $autoevaluation->autoevaluacion_id,
        ]);
    }
}
