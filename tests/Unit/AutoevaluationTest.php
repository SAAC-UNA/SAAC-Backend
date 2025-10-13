<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Autoevaluation;

class AutoevaluationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_creates_an_autoevaluation()
    {
        // Prueba de creación de autoevaluación
        $career = \App\Models\Career::factory()->create();
        $campus = \App\Models\Campus::factory()->create();
        $careerCampus = \App\Models\CareerCampus::factory()->create([
            'carrera_id' => $career->carrera_id,
            'sede_id' => $campus->sede_id,
        ]);
        $cycle = \App\Models\AccreditationCycle::factory()->create([
            'carrera_sede_id' => $careerCampus->carrera_sede_id,
        ]);
        $process = \App\Models\Process::factory()->create([
            'ciclo_acreditacion_id' => $cycle->ciclo_acreditacion_id,
        ]);
        $autoevaluation = \App\Models\Autoevaluation::factory()->create([
            'proceso_id' => $process->proceso_id,
        ]);
        $this->assertDatabaseHas('AUTOEVALUACION', [
            'autoevaluacion_id' => $autoevaluation->autoevaluacion_id,
        ]);
    }

    /** @test */
    public function it_requires_proceso_id_field()
    {
    // Prueba de validación: campo proceso_id es obligatorio
    $this->expectException(\Illuminate\Database\QueryException::class);
    \App\Models\Autoevaluation::factory()->create(['proceso_id' => null]);
    }

    /** @test */
    public function it_updates_an_autoevaluation()
    {
        // Prueba de actualización de autoevaluación
        $career = \App\Models\Career::factory()->create();
        $campus = \App\Models\Campus::factory()->create();
        $careerCampus = \App\Models\CareerCampus::factory()->create([
            'carrera_id' => $career->carrera_id,
            'sede_id' => $campus->sede_id,
        ]);
        $cycle = \App\Models\AccreditationCycle::factory()->create([
            'carrera_sede_id' => $careerCampus->carrera_sede_id,
        ]);
        $process = \App\Models\Process::factory()->create([
            'ciclo_acreditacion_id' => $cycle->ciclo_acreditacion_id,
        ]);
        $autoevaluation = \App\Models\Autoevaluation::factory()->create([
            'proceso_id' => $process->proceso_id,
            'fecha_inicio' => '2025-01-01',
        ]);
        $autoevaluation->update(['fecha_inicio' => '2025-09-28']);
        $this->assertDatabaseHas('AUTOEVALUACION', ['fecha_inicio' => '2025-09-28']);
    }

    /** @test */
    public function it_deletes_an_autoevaluation()
    {
        // Prueba de eliminación de autoevaluación
        $career = \App\Models\Career::factory()->create();
        $campus = \App\Models\Campus::factory()->create();
        $careerCampus = \App\Models\CareerCampus::factory()->create([
            'carrera_id' => $career->carrera_id,
            'sede_id' => $campus->sede_id,
        ]);
        $cycle = \App\Models\AccreditationCycle::factory()->create([
            'carrera_sede_id' => $careerCampus->carrera_sede_id,
        ]);
        $process = \App\Models\Process::factory()->create([
            'ciclo_acreditacion_id' => $cycle->ciclo_acreditacion_id,
        ]);
        $autoevaluation = \App\Models\Autoevaluation::factory()->create([
            'proceso_id' => $process->proceso_id,
        ]);
        $autoevaluation->delete();
        $this->assertDatabaseMissing('AUTOEVALUACION', ['autoevaluacion_id' => $autoevaluation->autoevaluacion_id]);
    }

    /** @test */
    public function an_autoevaluation_belongs_to_process()
    {
        // Prueba de relación belongsTo con Process
        $career = \App\Models\Career::factory()->create();
        $campus = \App\Models\Campus::factory()->create();
        $careerCampus = \App\Models\CareerCampus::factory()->create([
            'carrera_id' => $career->carrera_id,
            'sede_id' => $campus->sede_id,
        ]);
        $cycle = \App\Models\AccreditationCycle::factory()->create([
            'carrera_sede_id' => $careerCampus->carrera_sede_id,
        ]);
        $process = \App\Models\Process::factory()->create([
            'ciclo_acreditacion_id' => $cycle->ciclo_acreditacion_id,
        ]);
        $autoevaluation = \App\Models\Autoevaluation::factory()->create([
            'proceso_id' => $process->proceso_id,
        ]);
        $this->assertEquals($process->proceso_id, $autoevaluation->process->proceso_id);
    }
}
