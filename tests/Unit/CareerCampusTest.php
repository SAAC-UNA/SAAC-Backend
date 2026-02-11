<?php

use App\Models\CareerCampus;


it('creates a career campus', function () {
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
});

it('requires carrera_id field', function () {
    // Prueba de validación: campo carrera_id es obligatorio
    $campus = \App\Models\Campus::factory()->create();
    CareerCampus::factory()->create([
        'carrera_id' => null,
        'sede_id' => $campus->sede_id,
    ]);
})->throws(\Illuminate\Database\QueryException::class);

it('requires sede_id field', function () {
    // Prueba de validación: campo sede_id es obligatorio
    $career = \App\Models\Career::factory()->create();
    CareerCampus::factory()->create([
        'carrera_id' => $career->carrera_id,
        'sede_id' => null,
    ]);
})->throws(\Illuminate\Database\QueryException::class);

it('updates a career campus', function () {
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
});

it('deletes a career campus', function () {
    // Prueba de eliminación de sede de carrera
    $career = \App\Models\Career::factory()->create();
    $campus = \App\Models\Campus::factory()->create();
    $careerCampus = CareerCampus::factory()->create([
        'carrera_id' => $career->carrera_id,
        'sede_id' => $campus->sede_id,
    ]);
    $careerCampus->delete();
    $this->assertDatabaseMissing('CARRERA_SEDE', ['carrera_sede_id' => $careerCampus->carrera_sede_id]);
});

it('career campus belongs to career', function () {
    // Prueba de relación belongsTo con Career
    $career = \App\Models\Career::factory()->create();
    $careerCampus = CareerCampus::factory()->create(['carrera_id' => $career->carrera_id]);
    expect($careerCampus->career->carrera_id)->toBe($career->carrera_id);
});

it('career campus belongs to campus', function () {
    // Prueba de relación belongsTo con Campus
    $campus = \App\Models\Campus::factory()->create();
    $careerCampus = CareerCampus::factory()->create(['sede_id' => $campus->sede_id]);
    expect($careerCampus->campus->sede_id)->toBe($campus->sede_id);
});

it('career campus has many accreditation cycles', function () {
    // Prueba de relación hasMany con AccreditationCycle
    $career = \App\Models\Career::factory()->create();
    $campus = \App\Models\Campus::factory()->create();
    $careerCampus = CareerCampus::factory()->create([
        'carrera_id' => $career->carrera_id,
        'sede_id' => $campus->sede_id,
    ]);
    $cycle = \App\Models\AccreditationCycle::factory()->create(['carrera_sede_id' => $careerCampus->carrera_sede_id]);
    expect($careerCampus->accreditationCycles->contains($cycle))->toBeTrue();
});
it('puede tener múltiples ciclos de acreditación', function () {
    $careerCampus = CareerCampus::factory()->create();
    
    $cycle1 = \App\Models\AccreditationCycle::factory()->create([
        'carrera_sede_id' => $careerCampus->carrera_sede_id,
        'nombre' => 'Ciclo 2024'
    ]);
    $cycle2 = \App\Models\AccreditationCycle::factory()->create([
        'carrera_sede_id' => $careerCampus->carrera_sede_id,
        'nombre' => 'Ciclo 2025'
    ]);
    
    expect($careerCampus->accreditationCycles)->toHaveCount(2);
    
    $cycleIds = $careerCampus->accreditationCycles->pluck('ciclo_acreditacion_id')->toArray();
    expect($cycleIds)->toContain($cycle1->ciclo_acreditacion_id)
        ->and($cycleIds)->toContain($cycle2->ciclo_acreditacion_id);
});