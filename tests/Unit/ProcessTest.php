<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Process;

class ProcessTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_process_belongs_to_accreditation_cycle()
    {
        $career = \App\Models\Career::factory()->create();
        $campus = \App\Models\Campus::factory()->create();
        $careerCampus = \App\Models\CareerCampus::factory()->create([
            'carrera_id' => $career->carrera_id,
            'sede_id' => $campus->sede_id,
        ]);
        $cycle = \App\Models\AccreditationCycle::factory()->create([
            'carrera_sede_id' => $careerCampus->carrera_sede_id,
        ]);
        $process = \App\Models\Process::factory()->create(['ciclo_acreditacion_id' => $cycle->ciclo_acreditacion_id]);
        $this->assertEquals($cycle->ciclo_acreditacion_id, $process->accreditationCycle->ciclo_acreditacion_id);
    }

    /** @test */
    public function a_process_has_one_autoevaluation()
    {
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
        $autoevaluation = \App\Models\Autoevaluation::factory()->create(['proceso_id' => $process->proceso_id]);
        $this->assertEquals($autoevaluation->autoevaluacion_id, $process->autoevaluation->autoevaluacion_id);
    }

    /** @test */
    public function a_process_has_one_improvement_commitment()
    {
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
        $commitment = \App\Models\ImprovementCommitment::factory()->create(['proceso_id' => $process->proceso_id]);
        $this->assertEquals($commitment->compromiso_mejora_id, $process->improvementCommitment->compromiso_mejora_id);
    }

    /** @test */
    public function it_creates_a_process()
    {
        $career = \App\Models\Career::factory()->create();
        $campus = \App\Models\Campus::factory()->create();
        $careerCampus = \App\Models\CareerCampus::factory()->create([
            'carrera_id' => $career->carrera_id,
            'sede_id' => $campus->sede_id,
        ]);
        $accreditationCycle = \App\Models\AccreditationCycle::factory()->create([
            'carrera_sede_id' => $careerCampus->carrera_sede_id,
        ]);
        $process = \App\Models\Process::factory()->create([
            'ciclo_acreditacion_id' => $accreditationCycle->ciclo_acreditacion_id,
        ]);
        $this->assertDatabaseHas('PROCESO', [
            'ciclo_acreditacion_id' => $accreditationCycle->ciclo_acreditacion_id,
            'proceso_id' => $process->proceso_id,
        ]);
    }

    /** @test */
    public function it_requires_ciclo_acreditacion_id_field()
    {
    $this->expectException(\Illuminate\Database\QueryException::class);
    \App\Models\Process::factory()->create(['ciclo_acreditacion_id' => null]);
    }

    /** @test */
    public function it_updates_a_process()
    {
        $career = \App\Models\Career::factory()->create();
        $campus = \App\Models\Campus::factory()->create();
        $careerCampus = \App\Models\CareerCampus::factory()->create([
            'carrera_id' => $career->carrera_id,
            'sede_id' => $campus->sede_id,
        ]);
        $accreditationCycle = \App\Models\AccreditationCycle::factory()->create([
            'carrera_sede_id' => $careerCampus->carrera_sede_id,
        ]);
        $process = \App\Models\Process::factory()->create([
            'ciclo_acreditacion_id' => $accreditationCycle->ciclo_acreditacion_id,
        ]);
        $newCareer = \App\Models\Career::factory()->create();
        $newCampus = \App\Models\Campus::factory()->create();
        $newCareerCampus = \App\Models\CareerCampus::factory()->create([
            'carrera_id' => $newCareer->carrera_id,
            'sede_id' => $newCampus->sede_id,
        ]);
        $newCycle = \App\Models\AccreditationCycle::factory()->create([
            'carrera_sede_id' => $newCareerCampus->carrera_sede_id,
        ]);
        $process->update(['ciclo_acreditacion_id' => $newCycle->ciclo_acreditacion_id]);
        $this->assertDatabaseHas('PROCESO', ['ciclo_acreditacion_id' => $newCycle->ciclo_acreditacion_id]);
    }

    /** @test */
    public function it_deletes_a_process()
    {
        $career = \App\Models\Career::factory()->create();
        $campus = \App\Models\Campus::factory()->create();
        $careerCampus = \App\Models\CareerCampus::factory()->create([
            'carrera_id' => $career->carrera_id,
            'sede_id' => $campus->sede_id,
        ]);
        $accreditationCycle = \App\Models\AccreditationCycle::factory()->create([
            'carrera_sede_id' => $careerCampus->carrera_sede_id,
        ]);
        $process = \App\Models\Process::factory()->create([
            'ciclo_acreditacion_id' => $accreditationCycle->ciclo_acreditacion_id,
        ]);
        $process->delete();
        $this->assertDatabaseMissing('PROCESO', ['proceso_id' => $process->proceso_id]);
    }
}
