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
        // Crear roles base
        Role::create(['name' => 'SuperUsuario', 'guard_name' => 'web']);
        Role::create(['name' => 'Administrador', 'guard_name' => 'web']);

        // Crear carreras y campus
        $careerIng = Career::factory()->create(['nombre' => 'Ingeniería']);
        $careerQuimi = Career::factory()->create(['nombre' => 'Química']);
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

        // Crear usuario administrador de Ingeniería
        /** @var \App\Models\User $adminInge */
        $adminInge = User::factory()->create(['email' => 'cristopher.montero.jimenez@una.ac.cr']);
        $adminInge->assignRole('Administrador');
        $adminInge->careers()->attach($careerIng->carrera_id);

        // Autenticación del usuario administrador
        $this->actingAs($adminInge);

        // Realizar la solicitud al endpoint protegido
        $response = $this->getJson('/api/estructura/procesos');

        // Validaciones
        $response->assertStatus(200);

        // Debe incluir el proceso de Ingeniería
        $response->assertJsonFragment([
            'proceso_id' => $processIng->proceso_id
        ]);

        // No debe incluir el proceso de Química
        $response->assertJsonMissing([
            'proceso_id' => $processQuimi->proceso_id
        ]);
    }

    #[Test]
    public function superusuario_can_see_all_processes()
    {
        // Crear roles
        Role::create(['name' => 'SuperUsuario', 'guard_name' => 'web']);
        Role::create(['name' => 'Administrador', 'guard_name' => 'web']);

        // Crear carreras y campus
        $careerIng = Career::factory()->create(['nombre' => 'Ingeniería']);
        $careerQuimi = Career::factory()->create(['nombre' => 'Química']);
        $campus = Campus::factory()->create();

        // Crear ciclos de acreditación con sus relaciones carrera-sede
        $cycleIng = AccreditationCycle::factory()->create([
            'carrera_sede_id' => CareerCampus::factory()->create([
                'carrera_id' => $careerIng->carrera_id,
                'sede_id' => $campus->sede_id
            ])->carrera_sede_id
        ]);

        $cycleQuimi = AccreditationCycle::factory()->create([
            'carrera_sede_id' => CareerCampus::factory()->create([
                'carrera_id' => $careerQuimi->carrera_id,
                'sede_id' => $campus->sede_id
            ])->carrera_sede_id
        ]);

        // Crear procesos asociados a cada ciclo
        $processIng = Process::factory()->create(['ciclo_acreditacion_id' => $cycleIng->ciclo_acreditacion_id]);
        $processQuimi = Process::factory()->create(['ciclo_acreditacion_id' => $cycleQuimi->ciclo_acreditacion_id]);

        // Crear usuario con rol SuperUsuario
        /** @var \App\Models\User $super */
        $super = User::factory()->create(['email' => 'pablo.castillo.quesada@una.cr']);
        $super->assignRole('SuperUsuario');

        // Autenticarse como SuperUsuario
        $this->actingAs($super);

        // Realizar la solicitud
        $response = $this->getJson('/api/estructura/procesos');

        // Validaciones
        $response->assertStatus(200);

        // Debe ver ambos procesos (sin restricción de carrera)
        $response->assertJsonFragment(['proceso_id' => $processIng->proceso_id]);
        $response->assertJsonFragment(['proceso_id' => $processQuimi->proceso_id]);
    }
}
