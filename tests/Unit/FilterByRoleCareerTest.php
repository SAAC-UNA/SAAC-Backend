<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Career;
use App\Models\Campus;
use App\Models\CareerCampus;
use App\Models\AccreditationCycle;
use App\Models\Process;
use Spatie\Permission\Models\Role;

class FilterByRoleCareerUnitTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_filters_processes_by_user_role_and_career()
    {
        // Crear roles
        Role::create(['name' => 'SuperUsuario']);
        Role::create(['name' => 'Administrador']);

        // Crear carreras
        $careerIng = Career::factory()->create();
        $careerEdu = Career::factory()->create();
        $campus = Campus::factory()->create();

        // Asociaciones
        $careerCampusIng = CareerCampus::factory()->create([
            'carrera_id' => $careerIng->carrera_id,
            'sede_id' => $campus->sede_id,
        ]);

        $careerCampusEdu = CareerCampus::factory()->create([
            'carrera_id' => $careerEdu->carrera_id,
            'sede_id' => $campus->sede_id,
        ]);

        // Ciclos y procesos
        $cycleIng = AccreditationCycle::factory()->create(['carrera_sede_id' => $careerCampusIng->carrera_sede_id]);
        $cycleEdu = AccreditationCycle::factory()->create(['carrera_sede_id' => $careerCampusEdu->carrera_sede_id]);

        $processIng = Process::factory()->create(['ciclo_acreditacion_id' => $cycleIng->ciclo_acreditacion_id]);
        $processEdu = Process::factory()->create(['ciclo_acreditacion_id' => $cycleEdu->ciclo_acreditacion_id]);

        // Usuario
        $adminInge = User::factory()->create();
        $adminInge->assignRole('Administrador');
        $adminInge->careers()->attach($careerIng->carrera_id);

        // Simular el filtro a nivel de modelo
        $filteredProcesses = Process::whereHas('accreditationCycle.careerCampus', function ($q) use ($adminInge) {
            $q->whereIn('carrera_id', $adminInge->careers->pluck('carrera_id'));
        })->get();

        // Aserciones
        $this->assertTrue($filteredProcesses->contains($processIng));
        $this->assertFalse($filteredProcesses->contains($processEdu));
    }
}
