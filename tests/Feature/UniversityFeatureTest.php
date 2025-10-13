<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\University;

class UniversityFeatureTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_and_retrieve_a_university()
    {
        $university = University::factory()->create([
            'nombre' => 'Universidad Estatal',
        ]);

        $found = University::where('nombre', 'Universidad Estatal')->first();
        $this->assertNotNull($found);
        $this->assertEquals('Universidad Estatal', $found->nombre);
    }
}
