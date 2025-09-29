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
        // Prueba de creación de facultad
        $university = \App\Models\University::factory()->create();
        $campus = \App\Models\Campus::factory()->create([
            'universidad_id' => $university->universidad_id,
        ]);
        $faculty = Faculty::factory()->create([
            'nombre' => 'Facultad de Ciencias',
            'universidad_id' => $university->universidad_id,
            'sede_id' => $campus->sede_id,
        ]);
        $this->assertDatabaseHas('FACULTAD', [
            'nombre' => 'Facultad de Ciencias',
        ]);
    }

    /** @test */
    public function it_requires_nombre_field()
    {
        // Prueba de validación: campo nombre es obligatorio
        $university = \App\Models\University::factory()->create();
        $campus = \App\Models\Campus::factory()->create([
            'universidad_id' => $university->universidad_id,
        ]);
        $this->expectException(\Illuminate\Database\QueryException::class);
        Faculty::factory()->create([
            'nombre' => null,
            'universidad_id' => $university->universidad_id,
            'sede_id' => $campus->sede_id,
        ]);
    }

    /** @test */
    public function it_updates_a_faculty()
    {
        // Prueba de actualización de facultad
        $university = \App\Models\University::factory()->create();
        $campus = \App\Models\Campus::factory()->create([
            'universidad_id' => $university->universidad_id,
        ]);
        $faculty = Faculty::factory()->create([
            'nombre' => 'Original',
            'universidad_id' => $university->universidad_id,
            'sede_id' => $campus->sede_id,
        ]);
        $faculty->update(['nombre' => 'Actualizado']);
        $this->assertDatabaseHas('FACULTAD', ['nombre' => 'Actualizado']);
    }

    /** @test */
    public function it_deletes_a_faculty()
    {
        // Prueba de eliminación de facultad
        $university = \App\Models\University::factory()->create();
        $campus = \App\Models\Campus::factory()->create([
            'universidad_id' => $university->universidad_id,
        ]);
        $faculty = Faculty::factory()->create([
            'universidad_id' => $university->universidad_id,
            'sede_id' => $campus->sede_id,
        ]);
        $faculty->delete();
        $this->assertDatabaseMissing('FACULTAD', ['facultad_id' => $faculty->facultad_id]);
    }

    /** @test */
    public function a_faculty_belongs_to_university()
    {
        // Prueba de relación belongsTo con University
        $university = \App\Models\University::factory()->create();
        $campus = \App\Models\Campus::factory()->create([
            'universidad_id' => $university->universidad_id,
        ]);
        $faculty = Faculty::factory()->create([
            'universidad_id' => $university->universidad_id,
            'sede_id' => $campus->sede_id,
        ]);
        $this->assertEquals($university->universidad_id, $faculty->university->universidad_id);
    }
}
