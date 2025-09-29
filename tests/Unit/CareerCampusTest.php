<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\CareerCampus;

class CareerCampusTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_creates_a_career_campus()
    {
        // Prueba de creación de sede de carrera
        $career = \App\Models\Career::factory()->create();
        $campus = \App\Models\Campus::factory()->create();
        $careerCampus = CareerCampus::factory()->create([
            'carrera_id' => $career->carrera_id,
            'sede_id' => $campus->sede_id,
        ]);
        $this->assertDatabaseHas('CARRERA_SEDE', [
            'carrera_sede_id' => $careerCampus->carrera_sede_id,
        ]);
    }

    /** @test */
    public function it_requires_carrera_id_field()
    {
        // Prueba de validación: campo carrera_id es obligatorio
        $campus = \App\Models\Campus::factory()->create();
        $this->expectException(\Illuminate\Database\QueryException::class);
        CareerCampus::factory()->create([
            'carrera_id' => null,
            'sede_id' => $campus->sede_id,
        ]);
    }

    /** @test */
    public function it_requires_sede_id_field()
    {
        // Prueba de validación: campo sede_id es obligatorio
        $career = \App\Models\Career::factory()->create();
        $this->expectException(\Illuminate\Database\QueryException::class);
        CareerCampus::factory()->create([
            'carrera_id' => $career->carrera_id,
            'sede_id' => null,
        ]);
    }

    /** @test */
    public function it_updates_a_career_campus()
    {
        // Prueba de actualización de sede de carrera
        $career1 = \App\Models\Career::factory()->create();
        $career2 = \App\Models\Career::factory()->create();
        $campus = \App\Models\Campus::factory()->create();
        $careerCampus = CareerCampus::factory()->create([
            'carrera_id' => $career1->carrera_id,
            'sede_id' => $campus->sede_id,
        ]);
        $careerCampus->update(['carrera_id' => $career2->carrera_id]);
        $this->assertDatabaseHas('CARRERA_SEDE', [
            'carrera_id' => $career2->carrera_id,
            'carrera_sede_id' => $careerCampus->carrera_sede_id
        ]);
    }

    /** @test */
    public function it_deletes_a_career_campus()
    {
        // Prueba de eliminación de sede de carrera
        $career = \App\Models\Career::factory()->create();
        $campus = \App\Models\Campus::factory()->create();
        $careerCampus = CareerCampus::factory()->create([
            'carrera_id' => $career->carrera_id,
            'sede_id' => $campus->sede_id,
        ]);
        $careerCampus->delete();
        $this->assertDatabaseMissing('CARRERA_SEDE', ['carrera_sede_id' => $careerCampus->carrera_sede_id]);
    }

    /** @test */
    public function a_career_campus_belongs_to_career()
    {
        // Prueba de relación belongsTo con Career
        $career = \App\Models\Career::factory()->create();
        $careerCampus = CareerCampus::factory()->create(['carrera_id' => $career->carrera_id]);
        $this->assertEquals($career->carrera_id, $careerCampus->career->carrera_id);
    }

    /** @test */
    public function a_career_campus_belongs_to_campus()
    {
        // Prueba de relación belongsTo con Campus
        $campus = \App\Models\Campus::factory()->create();
        $careerCampus = CareerCampus::factory()->create(['sede_id' => $campus->sede_id]);
        $this->assertEquals($campus->sede_id, $careerCampus->campus->sede_id);
    }

    /** @test */
    public function a_career_campus_has_many_accreditation_cycles()
    {
        // Prueba de relación hasMany con AccreditationCycle
        $career = \App\Models\Career::factory()->create();
        $campus = \App\Models\Campus::factory()->create();
        $careerCampus = CareerCampus::factory()->create([
            'carrera_id' => $career->carrera_id,
            'sede_id' => $campus->sede_id,
        ]);
        $cycle = \App\Models\AccreditationCycle::factory()->create(['carrera_sede_id' => $careerCampus->carrera_sede_id]);
        $this->assertTrue($careerCampus->accreditationCycles->contains($cycle));
    }
}
