<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Career;
use App\Models\Campus;
use App\Models\CareerCampus;
use App\Models\AccreditationCycle;
use App\Models\Process;
use Spatie\Permission\Models\Role;
use PHPUnit\Framework\Attributes\Test;

class FilterByRoleCareerTest extends TestCase
{
    use RefreshDatabase;

     #[Test]
    public function admin_carrera_only_sees_processes_of_his_own_career()
    {
        // Crear roles
        Role::create(['name' => 'SuperUsuario', 'guard_name' => 'web']);
        Role::create(['name' => 'Administrador', 'guard_name' => 'web']);

        // Crear carreras y campus
        $careerIng = Career::factory()->create(['nombre' => 'Ingeniería']);
        $careerEdu = Career::factory()->create(['nombre' => 'Educación']);
        $campus = Campus::factory()->create();

        // Relación carrera-sede
        $careerCampusIng = CareerCampus::factory()->create([
            'carrera_id' => $careerIng->carrera_id,
            'sede_id' => $campus->sede_id,
        ]);
        $careerCampusEdu = CareerCampus::factory()->create([
            'carrera_id' => $careerEdu->carrera_id,
            'sede_id' => $campus->sede_id,
        ]);

        //  Crear ciclos y procesos
        $cycleIng = AccreditationCycle::factory()->create(['carrera_sede_id' => $careerCampusIng->carrera_sede_id]);
        $cycleEdu = AccreditationCycle::factory()->create(['carrera_sede_id' => $careerCampusEdu->carrera_sede_id]);

        $processIng = Process::factory()->create(['ciclo_acreditacion_id' => $cycleIng->ciclo_acreditacion_id]);
        $processEdu = Process::factory()->create(['ciclo_acreditacion_id' => $cycleEdu->ciclo_acreditacion_id]);

        //  Crear usuario administrador de Ingeniería
        /** @var \App\Models\User $adminInge */
        $adminInge = User::factory()->create(['email' => 'cristopher.montero.jimenez@una.ac.cr']);
        $adminInge->assignRole('Administrador');
        $adminInge->careers()->attach($careerIng->carrera_id);

        // Autenticarse como el admin de Ingeniería
        $this->actingAs($adminInge);

        //  Hacer la solicitud al endpoint real
        $response = $this->getJson('/api/estructura/procesos');

        // Validar resultados
        $response->assertStatus(200);

        // Debe contener solo el proceso de su carrera
        $response->assertJsonFragment([
            'proceso_id' => $processIng->proceso_id
        ]);

        // No debe mostrar procesos de otra carrera
        $response->assertJsonMissing([
            'proceso_id' => $processEdu->proceso_id
        ]);
    }

     #[Test]
    public function superusuario_can_see_all_processes()
    {
        Role::create(['name' => 'SuperUsuario', 'guard_name' => 'web']);
        Role::create(['name' => 'Administrador', 'guard_name' => 'web']);

        $careerIng = Career::factory()->create(['nombre' => 'Ingeniería']);
        $careerEdu = Career::factory()->create(['nombre' => 'Educación']);
        $campus = Campus::factory()->create();

        $cycleIng = AccreditationCycle::factory()->create([
            'carrera_sede_id' => CareerCampus::factory()->create([
                'carrera_id' => $careerIng->carrera_id,
                'sede_id' => $campus->sede_id
            ])->carrera_sede_id
        ]);

        $cycleEdu = AccreditationCycle::factory()->create([
            'carrera_sede_id' => CareerCampus::factory()->create([
                'carrera_id' => $careerEdu->carrera_id,
                'sede_id' => $campus->sede_id
            ])->carrera_sede_id
        ]);

        $processIng = Process::factory()->create(['ciclo_acreditacion_id' => $cycleIng->ciclo_acreditacion_id]);
        $processEdu = Process::factory()->create(['ciclo_acreditacion_id' => $cycleEdu->ciclo_acreditacion_id]);
        /** @var \App\Models\User $super */
        $super = User::factory()->create(['email' => 'pablo.castillo.quesada@una.cr']);
        $super->assignRole('SuperUsuario');

        $this->actingAs($super);

        $response = $this->getJson('/api/estructura/procesos');
        $response->assertStatus(200);

        // El superusuario ve ambos procesos
        $response->assertJsonFragment(['proceso_id' => $processIng->proceso_id]);
        $response->assertJsonFragment(['proceso_id' => $processEdu->proceso_id]);
    }
}
