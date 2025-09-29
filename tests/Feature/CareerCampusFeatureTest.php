<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\CareerCampus;

class CareerCampusFeatureTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_and_retrieve_a_career_campus()
    {
        $careerCampus = CareerCampus::factory()->create();
        $found = CareerCampus::find($careerCampus->carrera_sede_id);
        $this->assertNotNull($found);
        $this->assertEquals($careerCampus->carrera_sede_id, $found->carrera_sede_id);
    }
}
