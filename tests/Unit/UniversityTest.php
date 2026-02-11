<?php

use App\Models\University;
use App\Models\Campus;

it('creates a university', function () {
    $university = University::factory()->create([
        'nombre' => 'Universidad Nacional',
    ]);
    
    $this->assertDatabaseHas('UNIVERSIDAD', [
        'nombre' => 'Universidad Nacional',
    ]);
});

it('requires nombre field', function () {
    University::factory()->create(['nombre' => null]);
})->throws(\Illuminate\Database\QueryException::class);

it('updates a university', function () {
    $university = University::factory()->create(['nombre' => 'Original']);
    $university->update(['nombre' => 'Actualizado']);
    
    $this->assertDatabaseHas('UNIVERSIDAD', ['nombre' => 'Actualizado']);
});

it('deletes a university', function () {
    $university = University::factory()->create();
    $universityId = $university->universidad_id;
    $university->delete();
    
    $this->assertDatabaseMissing('UNIVERSIDAD', ['universidad_id' => $universityId]);
});

it('has many campuses', function () {
    $university = University::factory()->create();
    $campus = Campus::factory()->create([
        'universidad_id' => $university->universidad_id,
    ]);
    $university->refresh();
    
    expect($university->campuses->contains($campus))->toBeTrue();
});
