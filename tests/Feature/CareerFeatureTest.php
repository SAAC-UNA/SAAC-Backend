<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Career;

class CareerFeatureTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_and_retrieve_a_career()
    {
        $career = Career::factory()->create([
            'nombre' => 'Medicina',
        ]);

        $found = Career::where('nombre', 'Medicina')->first();
        $this->assertNotNull($found);
        $this->assertEquals('Medicina', $found->nombre);
    }
}
