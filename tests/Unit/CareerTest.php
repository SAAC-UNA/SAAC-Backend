<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Career;

class CareerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_creates_a_career()
    {
        $career = Career::factory()->create([
            'nombre' => 'Ingeniería',
        ]);

        $this->assertDatabaseHas('CARRERA', [
            'nombre' => 'Ingeniería',
        ]);
    }
}
