<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Faculty;

class FacultyFeatureTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_and_retrieve_a_faculty()
    {
        $faculty = Faculty::factory()->create([
            'nombre' => 'Facultad de Letras',
        ]);

        $found = Faculty::where('nombre', 'Facultad de Letras')->first();
        $this->assertNotNull($found);
        $this->assertEquals('Facultad de Letras', $found->nombre);
    }
}
