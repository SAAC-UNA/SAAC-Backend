<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\ImprovementCommitment;

class ImprovementCommitmentTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_improvement_commitment_belongs_to_process()
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
        $this->assertEquals($process->proceso_id, $commitment->process->proceso_id);
    }

    /** @test */
    public function it_creates_an_improvement_commitment()
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
        $commitment = \App\Models\ImprovementCommitment::factory()->create([
            'proceso_id' => $process->proceso_id,
        ]);
        $this->assertDatabaseHas('COMPROMISO_MEJORA', [
            'compromiso_mejora_id' => $commitment->compromiso_mejora_id,
        ]);
    }

    /** @test */
    public function it_requires_proceso_id_field()
    {
    $this->expectException(\Illuminate\Database\QueryException::class);
    \App\Models\ImprovementCommitment::factory()->create(['proceso_id' => null]);
    }

    /** @test */
    public function it_updates_an_improvement_commitment()
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
        $commitment = \App\Models\ImprovementCommitment::factory()->create([
            'proceso_id' => $process->proceso_id,
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
        $newProcess = \App\Models\Process::factory()->create([
            'ciclo_acreditacion_id' => $newCycle->ciclo_acreditacion_id,
        ]);
        $commitment->update(['proceso_id' => $newProcess->proceso_id]);
        $this->assertDatabaseHas('COMPROMISO_MEJORA', ['proceso_id' => $newProcess->proceso_id]);
    }

    /** @test */
    public function it_deletes_an_improvement_commitment()
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
        $commitment = \App\Models\ImprovementCommitment::factory()->create([
            'proceso_id' => $process->proceso_id,
        ]);
        $commitment->delete();
        $this->assertDatabaseMissing('COMPROMISO_MEJORA', ['compromiso_mejora_id' => $commitment->compromiso_mejora_id]);
    }
}
