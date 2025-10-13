<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Career;

class CareerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_creates_a_career()
    {
        $career = Career::factory()->create([
            'nombre' => 'Ingeniería',
        ]);
        $this->assertDatabaseHas('CARRERA', [
            'nombre' => 'Ingeniería',
        ]);
    }
    
    /** @test */
    public function it_requires_nombre_field()
    {
        $this->expectException(\Illuminate\Database\QueryException::class);
        Career::factory()->create(['nombre' => null]);
    }
    
    /** @test */
    public function it_updates_a_career()
    {
        $career = Career::factory()->create(['nombre' => 'Original']);
        $career->update(['nombre' => 'Actualizado']);
    
        $this->assertDatabaseHas('CARRERA', ['nombre' => 'Actualizado']);
    }
    
    /** @test */
    public function it_deletes_a_career()
    {
        $career = Career::factory()->create();
        $career->delete();
    
        $this->assertDatabaseMissing('CARRERA', ['carrera_id' => $career->carrera_id]);
    }
}
