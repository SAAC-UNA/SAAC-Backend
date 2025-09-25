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
        $autoevaluation = Autoevaluation::factory()->create([
            'nombre' => 'Autoevaluación 2025',
        ]);

        $this->assertDatabaseHas('AUTOEVALUACION', [
            'nombre' => 'Autoevaluación 2025',
        ]);
    }
}
