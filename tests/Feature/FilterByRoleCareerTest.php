<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Career;
use App\Models\Campus;
use App\Models\CareerCampus;
use App\Models\AccreditationCycle;
use App\Models\Process;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

it('admin_carrera_only_sees_processes_of_his_own_career_campus', function () {
    Permission::firstOrCreate([
        'name' => 'procesos.view',
        'guard_name' => 'api',
    ]);

        // Crear roles base
        Role::create(['name' => 'Superusuario', 'guard_name' => 'api']);
    $adminRole = Role::create(['name' => 'Administrador', 'guard_name' => 'api']);
    $adminRole->givePermissionTo('procesos.view');

        // Crear carreras y campus
        $careerIng = Career::factory()->create(['nombre' => 'Ingeniería en Sistemas']);
        $careerQuimi = Career::factory()->create(['nombre' => 'Química']);
        $campus = Campus::factory()->create(['nombre' => 'Sede Central']);

        // Asociar carreras con la sede (CareerCampus)
        $careerCampusIng = CareerCampus::factory()->create([
            'carrera_id' => $careerIng->carrera_id,
            'sede_id' => $campus->sede_id,
        ]);
        $careerCampusQuimi = CareerCampus::factory()->create([
            'carrera_id' => $careerQuimi->carrera_id,
            'sede_id' => $campus->sede_id,
        ]);

        // Crear ciclos de acreditación
        $cycleIng = AccreditationCycle::factory()->create([
            'carrera_sede_id' => $careerCampusIng->carrera_sede_id,
        ]);
        $cycleQuimi = AccreditationCycle::factory()->create([
            'carrera_sede_id' => $careerCampusQuimi->carrera_sede_id,
        ]);

        // Crear procesos asociados a cada ciclo
        $processIng = Process::factory()->create([
            'ciclo_acreditacion_id' => $cycleIng->ciclo_acreditacion_id,
        ]);
        $processQuimi = Process::factory()->create([
            'ciclo_acreditacion_id' => $cycleQuimi->ciclo_acreditacion_id,
        ]);
         
          /** @var \App\Models\User $adminInge */
        // Crear usuario administrador de Ingeniería
        $adminInge = User::factory()->create(['email' => 'cristopher.montero.jimenez@una.ac.cr']);
        $adminInge->assignRole('Administrador');

        // Asociar carrera Ingeniería al usuario
        $adminInge->careers()->attach($careerIng->carrera_id);

        // Autenticación
        Sanctum::actingAs($adminInge, ['api'], 'sanctum');

        // Llamar al endpoint
        $response = $this->getJson('/api/estructura/procesos');

        // Validaciones
        $response->assertStatus(200);
        $response->assertJsonFragment(['proceso_id' => $processIng->proceso_id]);
        $response->assertJsonMissing(['proceso_id' => $processQuimi->proceso_id]);
});

it('superusuario_can_see_all_processes', function () {
    Permission::firstOrCreate([
        'name' => 'procesos.view',
        'guard_name' => 'api',
    ]);

    $superRole = Role::create(['name' => 'Superusuario', 'guard_name' => 'api']);
    $superRole->givePermissionTo('procesos.view');
        Role::create(['name' => 'Administrador', 'guard_name' => 'api']);

        // Crear carreras y campus
        $careerIng = Career::factory()->create(['nombre' => 'Ingeniería']);
        $careerQuimi = Career::factory()->create(['nombre' => 'Química']);
        $campus = Campus::factory()->create();

        // Crear relaciones carrera-sede
        $careerCampusIng = CareerCampus::factory()->create([
            'carrera_id' => $careerIng->carrera_id,
            'sede_id' => $campus->sede_id,
        ]);
        $careerCampusQuimi = CareerCampus::factory()->create([
            'carrera_id' => $careerQuimi->carrera_id,
            'sede_id' => $campus->sede_id,
        ]);

        // Crear ciclos
        $cycleIng = AccreditationCycle::factory()->create([
            'carrera_sede_id' => $careerCampusIng->carrera_sede_id,
        ]);
        $cycleQuimi = AccreditationCycle::factory()->create([
            'carrera_sede_id' => $careerCampusQuimi->carrera_sede_id,
        ]);

        // Crear procesos
        $processIng = Process::factory()->create(['ciclo_acreditacion_id' => $cycleIng->ciclo_acreditacion_id]);
        $processQuimi = Process::factory()->create(['ciclo_acreditacion_id' => $cycleQuimi->ciclo_acreditacion_id]);
          /** @var \App\Models\User $super */
        // Crear superusuario
        $super = User::factory()->create(['email' => 'pablo.castillo.quesada@una.cr']);
        $super->assignRole('Superusuario');

        Sanctum::actingAs($super, ['api'], 'sanctum');

        $response = $this->getJson('/api/estructura/procesos');

        $response->assertStatus(200);
        $response->assertJsonFragment(['proceso_id' => $processIng->proceso_id]);
        $response->assertJsonFragment(['proceso_id' => $processQuimi->proceso_id]);
});
