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
        // Prueba de creación de campus
        $university = \App\Models\University::factory()->create();
        $campus = Campus::factory()->create([
            'nombre' => 'Campus Central',
            'universidad_id' => $university->universidad_id,
        ]);
        $this->assertDatabaseHas('SEDE', [
            'nombre' => 'Campus Central',
        ]);
    }

    /** @test */
    public function it_requires_nombre_field()
    {
        // Prueba de validación: campo nombre es obligatorio
        $university = \App\Models\University::factory()->create();
        $this->expectException(\Illuminate\Database\QueryException::class);
        Campus::factory()->create([
            'nombre' => null,
            'universidad_id' => $university->universidad_id,
        ]);
    }

    /** @test */
    public function it_requires_universidad_id_field()
    {
        // Prueba de validación: campo universidad_id es obligatorio
        $this->expectException(\Illuminate\Database\QueryException::class);
        Campus::factory()->create([
            'nombre' => 'Campus sin universidad',
            'universidad_id' => null,
        ]);
    }

    /** @test */
    public function it_updates_a_campus()
    {
        // Prueba de actualización de campus
        $university = \App\Models\University::factory()->create();
        $campus = Campus::factory()->create([
            'nombre' => 'Original',
            'universidad_id' => $university->universidad_id,
        ]);
        $campus->update(['nombre' => 'Actualizado']);
        $this->assertDatabaseHas('SEDE', ['nombre' => 'Actualizado']);
    }

    /** @test */
    public function it_deletes_a_campus()
    {
        // Prueba de eliminación de campus
        $university = \App\Models\University::factory()->create();
        $campus = Campus::factory()->create([
            'universidad_id' => $university->universidad_id,
        ]);
        $campus->delete();
        $this->assertDatabaseMissing('SEDE', ['sede_id' => $campus->sede_id]);
    }

    /** @test */
    public function a_campus_belongs_to_university()
    {
        // Prueba de relación belongsTo con University
        $university = \App\Models\University::factory()->create();
        $campus = Campus::factory()->create(['universidad_id' => $university->universidad_id]);
        $this->assertEquals($university->universidad_id, $campus->university->universidad_id);
    }

    /** @test */
    public function a_campus_has_many_faculties()
    {
        // Prueba de relación hasMany con Faculty
        $university = \App\Models\University::factory()->create();
        $campus = Campus::factory()->create([
            'universidad_id' => $university->universidad_id,
        ]);
        $faculty = \App\Models\Faculty::factory()->create([
            'sede_id' => $campus->sede_id,
            'universidad_id' => $university->universidad_id,
        ]);
        $this->assertTrue($campus->faculties->contains($faculty));
    }
}

