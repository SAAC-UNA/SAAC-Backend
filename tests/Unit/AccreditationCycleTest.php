<?php

namespace Tests\Unit;

use App\Models\AccreditationCycle;
use App\Models\Career;
use App\Models\Campus;
use App\Models\CareerCampus;
use App\Models\StructureModel;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccreditationCycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_an_accreditation_cycle(): void
    {
        $career = Career::factory()->create();
        $campus = Campus::factory()->create();
        $careerCampus = CareerCampus::factory()->create([
            'carrera_id' => $career->carrera_id,
            'sede_id'    => $campus->sede_id,
        ]);
        AccreditationCycle::factory()->create([
            'nombre'          => 'Ciclo 2025',
            'carrera_sede_id' => $careerCampus->carrera_sede_id,
        ]);

        $this->assertDatabaseHas('CICLO_ACREDITACION', [
            'nombre' => 'Ciclo 2025',
        ]);
    }

    public function test_requires_nombre_field(): void
    {
        $this->expectException(QueryException::class);

        $career = Career::factory()->create();
        $campus = Campus::factory()->create();
        $careerCampus = CareerCampus::factory()->create([
            'carrera_id' => $career->carrera_id,
            'sede_id'    => $campus->sede_id,
        ]);
        AccreditationCycle::factory()->create([
            'nombre'          => null,
            'carrera_sede_id' => $careerCampus->carrera_sede_id,
        ]);
    }

    public function test_updates_an_accreditation_cycle(): void
    {
        $career = Career::factory()->create();
        $campus = Campus::factory()->create();
        $careerCampus = CareerCampus::factory()->create([
            'carrera_id' => $career->carrera_id,
            'sede_id'    => $campus->sede_id,
        ]);
        $cycle = AccreditationCycle::factory()->create([
            'nombre'          => 'Original',
            'carrera_sede_id' => $careerCampus->carrera_sede_id,
        ]);
        $cycle->update(['nombre' => 'Actualizado']);

        $this->assertDatabaseHas('CICLO_ACREDITACION', ['nombre' => 'Actualizado']);
    }

    public function test_deletes_an_accreditation_cycle(): void
    {
        $career = Career::factory()->create();
        $campus = Campus::factory()->create();
        $careerCampus = CareerCampus::factory()->create([
            'carrera_id' => $career->carrera_id,
            'sede_id'    => $campus->sede_id,
        ]);
        $cycle = AccreditationCycle::factory()->create([
            'carrera_sede_id' => $careerCampus->carrera_sede_id,
        ]);
        $cycle->delete();

        $this->assertDatabaseMissing('CICLO_ACREDITACION', ['ciclo_acreditacion_id' => $cycle->ciclo_acreditacion_id]);
    }

    public function test_accreditation_cycle_belongs_to_career_campus(): void
    {
        $career = Career::factory()->create();
        $campus = Campus::factory()->create();
        $careerCampus = CareerCampus::factory()->create([
            'carrera_id' => $career->carrera_id,
            'sede_id'    => $campus->sede_id,
        ]);
        $cycle = AccreditationCycle::factory()->create([
            'carrera_sede_id' => $careerCampus->carrera_sede_id,
        ]);

        $this->assertSame($careerCampus->carrera_sede_id, $cycle->careerCampus->carrera_sede_id);
    }

    public function test_accreditation_cycle_has_many_processes(): void
    {
        $career = Career::factory()->create();
        $campus = Campus::factory()->create();
        $careerCampus = CareerCampus::factory()->create([
            'carrera_id' => $career->carrera_id,
            'sede_id'    => $campus->sede_id,
        ]);
        $cycle = AccreditationCycle::factory()->create([
            'carrera_sede_id' => $careerCampus->carrera_sede_id,
        ]);
        $process = \App\Models\Process::factory()->create([
            'ciclo_acreditacion_id' => $cycle->ciclo_acreditacion_id,
        ]);

        $this->assertTrue($cycle->processes->contains($process));
    }

    // ─── HU-030: helpers de estado ───────────────────────────────────────────

    public function test_isActive_retorna_true_solo_cuando_estado_es_activo_AC4(): void
    {
        $careerCampus = CareerCampus::factory()->create();

        $activo     = AccreditationCycle::factory()->create(['carrera_sede_id' => $careerCampus->carrera_sede_id, 'estado' => 'activo']);
        $inactivo   = AccreditationCycle::factory()->create(['carrera_sede_id' => $careerCampus->carrera_sede_id, 'estado' => 'inactivo']);
        $completado = AccreditationCycle::factory()->create(['carrera_sede_id' => $careerCampus->carrera_sede_id, 'estado' => 'completado']);

        $this->assertTrue($activo->isActive());
        $this->assertFalse($inactivo->isActive());
        $this->assertFalse($completado->isActive());
    }

    public function test_isEditable_retorna_true_solo_para_ciclo_activo_AC4(): void
    {
        $careerCampus = CareerCampus::factory()->create();

        $activo     = AccreditationCycle::factory()->create(['carrera_sede_id' => $careerCampus->carrera_sede_id, 'estado' => 'activo']);
        $inactivo   = AccreditationCycle::factory()->create(['carrera_sede_id' => $careerCampus->carrera_sede_id, 'estado' => 'inactivo']);
        $completado = AccreditationCycle::factory()->create(['carrera_sede_id' => $careerCampus->carrera_sede_id, 'estado' => 'completado']);

        $this->assertTrue($activo->isEditable());
        $this->assertFalse($inactivo->isEditable());
        $this->assertFalse($completado->isEditable());
    }

    public function test_scope_active_filtra_solo_ciclos_activos(): void
    {
        $careerCampus = CareerCampus::factory()->create();

        AccreditationCycle::factory()->create(['carrera_sede_id' => $careerCampus->carrera_sede_id, 'estado' => 'activo']);
        AccreditationCycle::factory()->create(['carrera_sede_id' => $careerCampus->carrera_sede_id, 'estado' => 'inactivo']);
        AccreditationCycle::factory()->create(['carrera_sede_id' => $careerCampus->carrera_sede_id, 'estado' => 'completado']);

        $activos = AccreditationCycle::where('carrera_sede_id', $careerCampus->carrera_sede_id)->active()->get();

        $this->assertCount(1, $activos);
        $this->assertSame('activo', $activos->first()->estado);
    }

    public function test_permite_crear_ciclo_inactivo_sin_restriccion_de_unicidad_activa(): void
    {
        $careerCampus = CareerCampus::factory()->create();
        $modelo       = StructureModel::factory()->create();

        AccreditationCycle::factory()->create([
            'carrera_sede_id'      => $careerCampus->carrera_sede_id,
            'modelo_estructura_id' => $modelo->modelo_estructura_id,
            'estado'               => 'inactivo',
        ]);
        $segundo = AccreditationCycle::factory()->create([
            'carrera_sede_id'      => $careerCampus->carrera_sede_id,
            'modelo_estructura_id' => $modelo->modelo_estructura_id,
            'estado'               => 'inactivo',
        ]);

        $this->assertDatabaseHas('CICLO_ACREDITACION', [
            'ciclo_acreditacion_id' => $segundo->ciclo_acreditacion_id,
            'estado'                => 'inactivo',
        ]);
    }
}
