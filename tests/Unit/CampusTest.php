<?php

use App\Models\Campus;
use App\Models\University;
use App\Models\Career;

it('creates a campus', function () {
    $university = University::factory()->create();
    $campus = Campus::factory()->create([
        'nombre' => 'Campus Central',
        'universidad_id' => $university->universidad_id,
    ]);
    
    $this->assertDatabaseHas('SEDE', [
        'nombre' => 'Campus Central',
    ]);
});

it('requires nombre field', function () {
    $university = University::factory()->create();
    Campus::factory()->create([
        'nombre' => null,
        'universidad_id' => $university->universidad_id,
    ]);
})->throws(\Illuminate\Database\QueryException::class);

it('requires universidad_id field', function () {
    Campus::factory()->create([
        'nombre' => 'Campus sin universidad',
        'universidad_id' => null,
    ]);
})->throws(\Illuminate\Database\QueryException::class);

it('updates a campus', function () {
    $university = University::factory()->create();
    $campus = Campus::factory()->create([
        'nombre' => 'Original',
        'universidad_id' => $university->universidad_id,
    ]);
    $campus->update(['nombre' => 'Actualizado']);
    
    $this->assertDatabaseHas('SEDE', ['nombre' => 'Actualizado']);
});

it('deletes a campus', function () {
    $university = University::factory()->create();
    $campus = Campus::factory()->create([
        'universidad_id' => $university->universidad_id,
    ]);
    $campusId = $campus->sede_id;
    $campus->delete();
    
    $this->assertDatabaseMissing('SEDE', ['sede_id' => $campusId]);
});

it('belongs to university', function () {
    $university = University::factory()->create();
    $campus = Campus::factory()->create(['universidad_id' => $university->universidad_id]);
    
    expect($campus->university->universidad_id)->toBe($university->universidad_id);
});

it('puede tener carreras asociadas', function () {
    $university = University::factory()->create();
    $campus = Campus::factory()->create([
        'universidad_id' => $university->universidad_id,
    ]);
    $career = Career::factory()->create();
    
    // Asociar carrera con campus a través de la tabla pivote CARRERA_SEDE
    $campus->careers()->attach($career->carrera_id);
    
    $campus->refresh();
    expect($campus->careers)->toHaveCount(1)
        ->and($campus->careers->first()->carrera_id)->toBe($career->carrera_id);
});

it('puede tener múltiples carreras asociadas', function () {
    $campus = Campus::factory()->create();
    $career1 = Career::factory()->create(['nombre' => 'Ingeniería']);
    $career2 = Career::factory()->create(['nombre' => 'Medicina']);
    
    $campus->careers()->attach([$career1->carrera_id, $career2->carrera_id]);
    
    $campus->refresh();
    expect($campus->careers)->toHaveCount(2);
    
    $careerIds = $campus->careers->pluck('carrera_id')->toArray();
    expect($careerIds)->toContain($career1->carrera_id)
        ->and($careerIds)->toContain($career2->carrera_id);
});

it('puede desasociar carreras', function () {
    $campus = Campus::factory()->create();
    $career = Career::factory()->create();
    
    $campus->careers()->attach($career->carrera_id);
    expect($campus->fresh()->careers)->toHaveCount(1);
    
    $campus->careers()->detach($career->carrera_id);
    expect($campus->fresh()->careers)->toHaveCount(0);
});

it('tiene campo activo por defecto', function () {
    $campus = Campus::factory()->create();
    
    expect($campus->activo)->toBeTrue();
});
