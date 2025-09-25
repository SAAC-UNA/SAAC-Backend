<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Component;

class ComponentTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_creates_a_component()
    {
        $component = Component::factory()->create([
            'nombre' => 'Componente 1',
        ]);

        $this->assertDatabaseHas('COMPONENTE', [
            'nombre' => 'Componente 1',
        ]);
    }
}
