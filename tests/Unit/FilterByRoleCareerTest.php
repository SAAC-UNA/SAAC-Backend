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
        // Crear roles en el sistema
        Role::create(['name' => 'SuperUsuario']);
        Role::create(['name' => 'Administrador']);

        // Crear carreras y sede
        $careerIng = Career::factory()->create(); // Ingeniería
        $careerQuimi = Career::factory()->create(); // Química
        $campus = Campus::factory()->create();

        // Asociar carreras con la sede (CareerCampus)
        $careerCampusIng = CareerCampus::factory()->create([
            'carrera_id' => $careerIng->carrera_id,
            'sede_id' => $campus->sede_id,
        ]);

        $careerCampusQuimi = CareerCampus::factory()->create([
            'carrera_id' => $careerQuimi->carrera_id,
            'sede_id' => $campus->sede_id,
        ]);

        // Crear ciclos de acreditación para cada carrera
        $cycleIng = AccreditationCycle::factory()->create(['carrera_sede_id' => $careerCampusIng->carrera_sede_id]);
        $cycleQuimi = AccreditationCycle::factory()->create(['carrera_sede_id' => $careerCampusQuimi->carrera_sede_id]);

        // Crear procesos asociados a cada ciclo
        $processIng = Process::factory()->create(['ciclo_acreditacion_id' => $cycleIng->ciclo_acreditacion_id]);
        $processQuimi = Process::factory()->create(['ciclo_acreditacion_id' => $cycleQuimi->ciclo_acreditacion_id]);

        // Crear usuario con rol "Administrador" asociado a Ingeniería
        $adminInge = User::factory()->create();
        $adminInge->assignRole('Administrador');
        $adminInge->careers()->attach($careerIng->carrera_id);

        // Aplicar el filtro: obtener solo los procesos de las carreras del usuario
        $filteredProcesses = Process::whereHas('accreditationCycle.careerCampus', function ($q) use ($adminInge) {
            $q->whereIn('carrera_id', $adminInge->careers->pluck('carrera_id'));
        })->get();

        // Verificar resultados esperados
        $this->assertTrue($filteredProcesses->contains($processIng));
        $this->assertFalse($filteredProcesses->contains($processQuimi));
    }
}
