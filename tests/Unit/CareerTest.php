<?php

use App\Models\Career;

it('creates a career', function () {
    $career = Career::factory()->create([
        'nombre' => 'Ingeniería',
    ]);
    
    $this->assertDatabaseHas('CARRERA', [
        'nombre' => 'Ingeniería',
    ]);
});

it('requires nombre field', function () {
    Career::factory()->create(['nombre' => null]);
})->throws(\Illuminate\Database\QueryException::class);

it('updates a career', function () {
    $career = Career::factory()->create(['nombre' => 'Original']);
    $career->update(['nombre' => 'Actualizado']);

    $this->assertDatabaseHas('CARRERA', ['nombre' => 'Actualizado']);
});

it('deletes a career', function () {
    $career = Career::factory()->create();
    $careerId = $career->carrera_id;
    $career->delete();

    $this->assertDatabaseMissing('CARRERA', ['carrera_id' => $careerId]);
});

it('puede asociarse con usuarios', function () {
    $career = Career::factory()->create();
    $user = \App\Models\User::factory()->create();
    
    $career->users()->attach($user->usuario_id);
    
    expect($career->users)->toHaveCount(1);
    expect($career->users->first()->usuario_id)->toBe($user->usuario_id);
});

it('puede asociarse con sedes', function () {
    $career = Career::factory()->create();
    $campus = \App\Models\Campus::factory()->create();
    
    $career->campuses()->attach($campus->sede_id);
    
    expect($career->campuses)->toHaveCount(1);
    expect($career->campuses->first()->sede_id)->toBe($campus->sede_id);
});

it('tiene campo activo por defecto', function () {
    $career = Career::factory()->create();
    
    expect($career->activo)->toBeTrue();
});
