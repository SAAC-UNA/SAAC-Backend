<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\University;

class UniversityTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_creates_a_university()
    {
        $university = University::factory()->create([
            'nombre' => 'Universidad Nacional',
        ]);

        $this->assertDatabaseHas('UNIVERSIDAD', [
            'nombre' => 'Universidad Nacional',
        ]);
    }
}
