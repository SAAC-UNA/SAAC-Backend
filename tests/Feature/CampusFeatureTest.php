<?php

use App\Models\Campus;
use App\Models\University;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

    // Crear usuario autenticado para las pruebas con Sanctum
    $this->user = User::factory()->create();
    $this->user->assignRole(Role::where('name', 'Superusuario')->where('guard_name', 'api')->first());
    Sanctum::actingAs($this->user);
});

it('puede listar todos los campuses', function () {
    $university = University::factory()->create();
    Campus::factory()->count(3)->create(['universidad_id' => $university->universidad_id]);
    
    $response = $this->getJson('/api/estructura/campuses');
    
    $response->assertStatus(200)
        ->assertJsonCount(3);
});

it('puede filtrar campuses por universidad_id', function () {
    $university1 = University::factory()->create();
    $university2 = University::factory()->create();
    
    Campus::factory()->count(2)->create(['universidad_id' => $university1->universidad_id]);
    Campus::factory()->count(3)->create(['universidad_id' => $university2->universidad_id]);
    
    $response = $this->getJson("/api/estructura/campuses?universidad_id={$university2->universidad_id}");
    
    $response->assertStatus(200)
        ->assertJsonCount(3);
});

it('puede obtener un campus específico', function () {
    $campus = Campus::factory()->create(['nombre' => 'Campus Test']);
    
    $response = $this->getJson("/api/estructura/campuses/{$campus->sede_id}");
    
    $response->assertStatus(200)
        ->assertJson([
            'nombre' => 'Campus Test',
            'sede_id' => $campus->sede_id,
        ]);
});

it('retorna 404 al buscar campus inexistente', function () {
    $response = $this->getJson('/api/estructura/campuses/99999');
    
    $response->assertStatus(404)
        ->assertJson(['message' => 'Campus no encontrado.']);
});

it('puede crear un campus', function () {
    $university = University::factory()->create();
    
    $data = [
        'nombre' => 'Campus Nuevo',
        'universidad_id' => $university->universidad_id,
        'activo' => true,
    ];
    
    $response = $this->postJson('/api/estructura/campuses', $data);

    $response->assertStatus(201)
        ->assertJsonPath('data.nombre', 'Campus Nuevo');
    
    $this->assertDatabaseHas('SEDE', [
        'nombre' => 'Campus Nuevo',
        'universidad_id' => $university->universidad_id,
    ]);
});

it('valida que el nombre sea requerido al crear', function () {
    $university = University::factory()->create();
    
    $data = [
        'universidad_id' => $university->universidad_id,
    ];
    
    $response = $this->postJson('/api/estructura/campuses', $data);
    
    $response->assertStatus(422)
        ->assertJsonValidationErrors(['nombre']);
});

it('valida que universidad_id sea requerido al crear', function () {
    $data = [
        'nombre' => 'Campus Sin Universidad',
    ];
    
    $response = $this->postJson('/api/estructura/campuses', $data);
    
    $response->assertStatus(422)
        ->assertJsonValidationErrors(['universidad_id']);
});

it('puede actualizar un campus', function () {
    $campus = Campus::factory()->create(['nombre' => 'Nombre Original']);
    
    $data = [
        'nombre' => 'Nombre Actualizado',
        'universidad_id' => $campus->universidad_id,
    ];
    
    $response = $this->putJson("/api/estructura/campuses/{$campus->sede_id}", $data);
    
    $response->assertStatus(200)
        ->assertJson([
            'message' => 'Campus actualizado correctamente.',
            'data' => [
                'nombre' => 'Nombre Actualizado',
            ],
        ]);
    
    $this->assertDatabaseHas('SEDE', [
        'sede_id' => $campus->sede_id,
        'nombre' => 'Nombre Actualizado',
    ]);
});

it('retorna 404 al actualizar campus inexistente', function () {
    $university = University::factory()->create();
    
    $data = [
        'nombre' => 'Nombre',
        'universidad_id' => $university->universidad_id,
    ];
    
    $response = $this->putJson('/api/estructura/campuses/99999', $data);
    
    $response->assertStatus(404)
        ->assertJson(['message' => 'Campus no encontrado.']);
});

it('puede eliminar un campus', function () {
    $campus = Campus::factory()->create();
    $campusId = $campus->sede_id;
    
    $response = $this->deleteJson("/api/estructura/campuses/{$campusId}");
    
    $response->assertStatus(204);
    $this->assertDatabaseMissing('SEDE', ['sede_id' => $campusId]);
});

it('retorna 404 al eliminar campus inexistente', function () {
    $response = $this->deleteJson('/api/estructura/campuses/99999');
    
    $response->assertStatus(404)
        ->assertJson(['message' => 'Campus no encontrado.']);
});

it('retorna 409 al eliminar campus con relaciones', function () {
    $campus = Campus::factory()->create();
    
    // Crear una relación que impida la eliminación (CareerCampus)
    $careerCampus = \App\Models\CareerCampus::factory()->create([
        'sede_id' => $campus->sede_id,
    ]);
    
    $response = $this->deleteJson("/api/estructura/campuses/{$campus->sede_id}");
    
    $response->assertStatus(409)
        ->assertJson([
            'message' => 'No se puede eliminar: tiene registros relacionados.',
            'code' => 'FK_CONSTRAINT',
        ]);
});

it('puede cambiar el estado activo de un campus', function () {
    $campus = Campus::factory()->create(['activo' => true]);
    
    $campus->update(['activo' => false]);
    
    expect($campus->fresh()->activo)->toBe(0);
    
    $campus->update(['activo' => true]);
    
    expect($campus->fresh()->activo)->toBe(1);
});
