<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Faculty;

class FacultyTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_creates_a_faculty()
    {
        $faculty = Faculty::factory()->create([
            'nombre' => 'Facultad de Ciencias',
        ]);

        $this->assertDatabaseHas('FACULTAD', [
            'nombre' => 'Facultad de Ciencias',
        ]);
    }
}
