<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Standard;

class StandardFeatureTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_and_retrieve_a_standard()
    {
        $standard = Standard::factory()->create([
            'nombre' => 'Estándar 2',
        ]);

        $found = Standard::where('nombre', 'Estándar 2')->first();
        $this->assertNotNull($found);
        $this->assertEquals('Estándar 2', $found->nombre);
    }
}
