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
        // Prueba de creación de universidad
        $university = University::factory()->create([
            'nombre' => 'Universidad Nacional',
        ]);
        $this->assertDatabaseHas('UNIVERSIDAD', [
            'nombre' => 'Universidad Nacional',
        ]);
    }

    /** @test */
    public function it_requires_nombre_field()
    {
        // Prueba de validación: campo nombre es obligatorio
        $this->expectException(\Illuminate\Database\QueryException::class);
        University::factory()->create(['nombre' => null]);
    }

    /** @test */
    public function it_updates_a_university()
    {
        // Prueba de actualización de universidad
        $university = University::factory()->create(['nombre' => 'Original']);
        $university->update(['nombre' => 'Actualizado']);
        $this->assertDatabaseHas('UNIVERSIDAD', ['nombre' => 'Actualizado']);
    }

    /** @test */
    public function it_deletes_a_university()
    {
    // Prueba de eliminación de universidad
    $university = University::factory()->create();
    $university->delete();
    $this->assertDatabaseMissing('UNIVERSIDAD', ['universidad_id' => $university->universidad_id]);
    }

    /** @test */
    public function a_university_has_many_faculties()
    {
        // Prueba de relación hasMany con Faculty
        $university = University::factory()->create();
        $campus = \App\Models\Campus::factory()->create([
            'universidad_id' => $university->universidad_id,
        ]);
        $faculty = \App\Models\Faculty::factory()->create([
            'universidad_id' => $university->universidad_id,
            'sede_id' => $campus->sede_id,
        ]);
        $university->refresh();
        $this->assertTrue($university->faculties->contains($faculty));
    }
}
