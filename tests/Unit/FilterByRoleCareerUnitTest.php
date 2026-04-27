<?php

use App\Models\User;
use App\Models\Career;
use App\Models\Campus;
use App\Models\CareerCampus;
use App\Models\AccreditationCycle;
use App\Models\Process;
use Spatie\Permission\Models\Role;

it('filters processes by user role and career campus', function () {
    
    Role::create(['name' => 'SuperUsuario', 'guard_name' => 'api']);
    Role::create(['name' => 'Administrador', 'guard_name' => 'api']);
    
    // Crear carreras y sede
    $careerIng = Career::factory()->create(['nombre' => 'Ingeniería en Sistemas']);
    $careerQuimi = Career::factory()->create(['nombre' => 'Química']);
    $campus = Campus::factory()->create(['nombre' => 'Sede Interuniversitaria']);

    // Relación carrera-sede
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
    $processIng = Process::factory()->create([
        'ciclo_acreditacion_id' => $cycleIng->ciclo_acreditacion_id,
    ]);
    $processQuimi = Process::factory()->create([
        'ciclo_acreditacion_id' => $cycleQuimi->ciclo_acreditacion_id,
    ]);

    // Crear usuario con rol Administrador (Ingeniería)
    $adminInge = User::factory()->create();
    $adminInge->assignRole('Administrador');
    $adminInge->careers()->attach($careerCampusIng->carrera_sede_id);

    // Aplicar el filtro por carrera_sede_id
    $filtered = Process::whereHas('accreditationCycle.careerCampus', function ($q) use ($adminInge) {
        $q->join('CARRERA', 'CARRERA_SEDE.carrera_id', '=', 'CARRERA.carrera_id')
          ->whereIn('CARRERA.carrera_id', $adminInge->careers->pluck('carrera_id'));
    })->get();

    expect($filtered->contains($processIng))->toBeTrue();
    expect($filtered->contains($processQuimi))->toBeFalse();
});
