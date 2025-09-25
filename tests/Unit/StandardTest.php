<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Standard;

class StandardTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_creates_a_standard()
    {
        $standard = Standard::factory()->create([
            'nombre' => 'Estándar 1',
        ]);

        $this->assertDatabaseHas('ESTANDAR', [
            'nombre' => 'Estándar 1',
        ]);
    }
}
