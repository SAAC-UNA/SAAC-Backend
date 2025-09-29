<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\AccreditationCycle;

class AccreditationCycleTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_creates_an_accreditation_cycle()
    {
        // Prueba de creación de ciclo de acreditación
        $career = \App\Models\Career::factory()->create();
        $campus = \App\Models\Campus::factory()->create();
        $careerCampus = \App\Models\CareerCampus::factory()->create([
            'carrera_id' => $career->carrera_id,
            'sede_id' => $campus->sede_id,
        ]);
        $cycle = AccreditationCycle::factory()->create([
            'nombre' => 'Ciclo 2025',
            'carrera_sede_id' => $careerCampus->carrera_sede_id,
        ]);
        $this->assertDatabaseHas('CICLO_ACREDITACION', [
            'nombre' => 'Ciclo 2025',
        ]);
    }

    /** @test */
    public function it_requires_nombre_field()
    {
        // Prueba de validación: campo nombre es obligatorio
        $career = \App\Models\Career::factory()->create();
        $campus = \App\Models\Campus::factory()->create();
        $careerCampus = \App\Models\CareerCampus::factory()->create([
            'carrera_id' => $career->carrera_id,
            'sede_id' => $campus->sede_id,
        ]);
        $this->expectException(\Illuminate\Database\QueryException::class);
        AccreditationCycle::factory()->create([
            'nombre' => null,
            'carrera_sede_id' => $careerCampus->carrera_sede_id,
        ]);
    }

    /** @test */
    public function it_updates_an_accreditation_cycle()
    {
        // Prueba de actualización de ciclo de acreditación
        $career = \App\Models\Career::factory()->create();
        $campus = \App\Models\Campus::factory()->create();
        $careerCampus = \App\Models\CareerCampus::factory()->create([
            'carrera_id' => $career->carrera_id,
            'sede_id' => $campus->sede_id,
        ]);
        $cycle = AccreditationCycle::factory()->create([
            'nombre' => 'Original',
            'carrera_sede_id' => $careerCampus->carrera_sede_id,
        ]);
        $cycle->update(['nombre' => 'Actualizado']);
        $this->assertDatabaseHas('CICLO_ACREDITACION', ['nombre' => 'Actualizado']);
    }

    /** @test */
    public function it_deletes_an_accreditation_cycle()
    {
        // Prueba de eliminación de ciclo de acreditación
        $career = \App\Models\Career::factory()->create();
        $campus = \App\Models\Campus::factory()->create();
        $careerCampus = \App\Models\CareerCampus::factory()->create([
            'carrera_id' => $career->carrera_id,
            'sede_id' => $campus->sede_id,
        ]);
        $cycle = AccreditationCycle::factory()->create([
            'carrera_sede_id' => $careerCampus->carrera_sede_id,
        ]);
        $cycle->delete();
        $this->assertDatabaseMissing('CICLO_ACREDITACION', ['ciclo_acreditacion_id' => $cycle->ciclo_acreditacion_id]);
    }

    /** @test */
    public function an_accreditation_cycle_belongs_to_career_campus()
    {
        // Prueba de relación belongsTo con CareerCampus
        $career = \App\Models\Career::factory()->create();
        $campus = \App\Models\Campus::factory()->create();
        $careerCampus = \App\Models\CareerCampus::factory()->create([
            'carrera_id' => $career->carrera_id,
            'sede_id' => $campus->sede_id,
        ]);
        $cycle = AccreditationCycle::factory()->create([
            'carrera_sede_id' => $careerCampus->carrera_sede_id,
        ]);
        $this->assertEquals($careerCampus->carrera_sede_id, $cycle->careerCampus->carrera_sede_id);
    }

    /** @test */
    public function an_accreditation_cycle_has_many_processes()
    {
        // Prueba de relación hasMany con Process
        $career = \App\Models\Career::factory()->create();
        $campus = \App\Models\Campus::factory()->create();
        $careerCampus = \App\Models\CareerCampus::factory()->create([
            'carrera_id' => $career->carrera_id,
            'sede_id' => $campus->sede_id,
        ]);
        $cycle = AccreditationCycle::factory()->create([
            'carrera_sede_id' => $careerCampus->carrera_sede_id,
        ]);
        $process = \App\Models\Process::factory()->create([
            'ciclo_acreditacion_id' => $cycle->ciclo_acreditacion_id,
        ]);
        $this->assertTrue($cycle->processes->contains($process));
    }
}
