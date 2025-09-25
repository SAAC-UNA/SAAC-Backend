<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\CareerCampus;

class CareerCampusTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_creates_a_career_campus()
    {
        $careerCampus = CareerCampus::factory()->create();
        $this->assertDatabaseHas('CARRERA_SEDE', [
            'carrera_sede_id' => $careerCampus->carrera_sede_id,
        ]);
    }
}
