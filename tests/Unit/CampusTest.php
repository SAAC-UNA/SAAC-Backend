<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Campus;

class CampusTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_creates_a_campus()
    {
        $campus = Campus::factory()->create([
            'nombre' => 'Campus Central',
        ]);

        $this->assertDatabaseHas('SEDE', [
            'nombre' => 'Campus Central',
        ]);
    }
}
