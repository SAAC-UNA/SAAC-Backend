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
        $autoevaluation = Autoevaluation::factory()->create([
            'nombre' => 'Autoevaluación 2026',
        ]);

        $found = Autoevaluation::where('nombre', 'Autoevaluación 2026')->first();
        $this->assertNotNull($found);
        $this->assertEquals('Autoevaluación 2026', $found->nombre);
    }
}
