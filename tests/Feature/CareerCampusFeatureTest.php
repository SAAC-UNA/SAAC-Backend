<?php

use App\Models\CareerCampus;
use App\Models\Career;
use App\Models\Campus;
use App\Models\AccreditationCycle;

it('puede crear y recuperar una carrera-sede', function () {
    $careerCampus = CareerCampus::factory()->create();
    $found = CareerCampus::find($careerCampus->carrera_sede_id);
    expect($found)->not->toBeNull();
    expect($found->carrera_sede_id)->toBe($careerCampus->carrera_sede_id);
});

it('puede filtrar por carrera', function () {
    $career1 = Career::factory()->create();
    $career2 = Career::factory()->create();
    
    CareerCampus::factory()->count(3)->create(['carrera_id' => $career1->carrera_id]);
    CareerCampus::factory()->count(2)->create(['carrera_id' => $career2->carrera_id]);
    
    $career1Items = CareerCampus::where('carrera_id', $career1->carrera_id)->get();
    $career2Items = CareerCampus::where('carrera_id', $career2->carrera_id)->get();
    
    expect($career1Items)->toHaveCount(3);
    expect($career2Items)->toHaveCount(2);
});

it('puede filtrar por sede', function () {
    $campus1 = Campus::factory()->create();
    $campus2 = Campus::factory()->create();
    
    CareerCampus::factory()->count(4)->create(['sede_id' => $campus1->sede_id]);
    CareerCampus::factory()->count(1)->create(['sede_id' => $campus2->sede_id]);
    
    $campus1Items = CareerCampus::where('sede_id', $campus1->sede_id)->get();
    $campus2Items = CareerCampus::where('sede_id', $campus2->sede_id)->get();
    
    expect($campus1Items)->toHaveCount(4);
    expect($campus2Items)->toHaveCount(1);
});

it('puede cargar relaciones eager loading', function () {
    $careerCampus = CareerCampus::factory()->create();
    
    $loaded = CareerCampus::with(['career', 'campus', 'accreditationCycles'])
        ->find($careerCampus->carrera_sede_id);
    
    expect($loaded->relationLoaded('career'))->toBeTrue();
    expect($loaded->relationLoaded('campus'))->toBeTrue();
    expect($loaded->relationLoaded('accreditationCycles'))->toBeTrue();
    expect($loaded->career)->toBeInstanceOf(Career::class);
    expect($loaded->campus)->toBeInstanceOf(Campus::class);
});

it('puede contar ciclos de acreditación asociados', function () {
    $careerCampus = CareerCampus::factory()->create();
    
    AccreditationCycle::factory()->count(5)->create([
        'carrera_sede_id' => $careerCampus->carrera_sede_id
    ]);
    
    $loaded = CareerCampus::withCount('accreditationCycles')
        ->find($careerCampus->carrera_sede_id);
    
    expect($loaded->accreditation_cycles_count)->toBe(5);
});

it('puede acceder a career y campus desde la relación', function () {
    $careerCampus = CareerCampus::factory()->create();
    
    expect($careerCampus->career)->toBeInstanceOf(Career::class);
    expect($careerCampus->campus)->toBeInstanceOf(Campus::class);
    expect($careerCampus->career->carrera_id)->toBe($careerCampus->carrera_id);
    expect($careerCampus->campus->sede_id)->toBe($careerCampus->sede_id);
});
