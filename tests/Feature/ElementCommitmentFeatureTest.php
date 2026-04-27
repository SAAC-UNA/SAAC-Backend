<?php

use App\Models\AccreditationCycle;
use App\Models\CareerCampus;
use App\Models\ElementCommitment;
use App\Models\Process;
use App\Models\Role;
use App\Models\StructureElement;
use App\Models\StructureModel;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

    $this->superusuario = User::factory()->create();
    $this->superusuario->assignRole(Role::where('name', 'Superusuario')->first());

    [$this->process, $this->rootElement] = featureElementCommitmentTreeBase();

    $this->childElement = StructureElement::factory()->create([
        'modelo_estructura_id' => $this->rootElement->modelo_estructura_id,
        'padre_id' => $this->rootElement->elemento_id,
        'activo' => true,
    ]);
});

it('returns 401 for unauthenticated list request', function () {
    $this->getJson('/api/compromisos-elementos')->assertStatus(401);
});

it('lists commitments for superusuario', function () {
    Sanctum::actingAs($this->superusuario);

    ElementCommitment::create([
        'proceso_id' => $this->process->proceso_id,
        'descripcion' => 'Compromiso de lista',
        'fecha_inicio' => now()->addDay()->toDateString(),
        'fecha_fin' => now()->addDays(8)->toDateString(),
        'estado' => 'Pendiente',
        'activo' => true,
    ]);

    $this->getJson('/api/compromisos-elementos')
        ->assertStatus(200)
        ->assertJsonPath('success', true);
});

it('creates element commitment via endpoint', function () {
    Sanctum::actingAs($this->superusuario);

    $assignedUser = User::factory()->create();

    $response = $this->postJson('/api/compromisos-elementos', [
        'proceso_id' => $this->process->proceso_id,
        'elemento_id' => $this->rootElement->elemento_id,
        'descripcion' => 'Compromiso flexible por endpoint',
        'fecha_inicio' => now()->addDay()->toDateString(),
        'fecha_fin' => now()->addDays(15)->toDateString(),
        'estado' => 'Pendiente',
        'elementos_asignar' => [[
            'elemento_id' => $this->childElement->elemento_id,
            'usuarios' => [$assignedUser->usuario_id],
            'comentario' => 'Asignado desde feature test',
        ]],
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('success', true);

    $this->assertDatabaseHas('COMPROMISO_MEJORA_ELEMENTO', [
        'descripcion' => 'Compromiso flexible por endpoint',
        'proceso_id' => $this->process->proceso_id,
    ]);
});

it('shows 404 when commitment does not exist', function () {
    Sanctum::actingAs($this->superusuario);

    $this->getJson('/api/compromisos-elementos/999999')
        ->assertStatus(404)
        ->assertJsonPath('success', false);
});

it('returns commitments by element endpoint', function () {
    Sanctum::actingAs($this->superusuario);

    $commitment = ElementCommitment::create([
        'proceso_id' => $this->process->proceso_id,
        'descripcion' => 'Compromiso para consulta por elemento',
        'fecha_inicio' => now()->addDay()->toDateString(),
        'fecha_fin' => now()->addDays(12)->toDateString(),
        'estado' => 'Pendiente',
        'activo' => true,
    ]);

    $response = $this->getJson('/api/compromisos-elementos/elemento/' . $this->rootElement->elemento_id)
        ->assertStatus(200)
        ->assertJsonPath('success', true);

    expect($response->json('data.data'))->toBeArray();
});

it('returns 422 when update request has no changes', function () {
    Sanctum::actingAs($this->superusuario);

    $commitment = ElementCommitment::create([
        'proceso_id' => $this->process->proceso_id,
        'descripcion' => 'Mismo valor',
        'fecha_inicio' => now()->addDay()->toDateString(),
        'fecha_fin' => now()->addDays(10)->toDateString(),
        'estado' => 'Pendiente',
        'activo' => true,
    ]);

    $this->putJson('/api/compromisos-elementos/' . $commitment->compromiso_elemento_id, [
        'descripcion' => 'Mismo valor',
    ])->assertStatus(422)
      ->assertJsonPath('success', false);
});

it('updates active status via endpoint', function () {
    Sanctum::actingAs($this->superusuario);

    $commitment = ElementCommitment::create([
        'proceso_id' => $this->process->proceso_id,
        'descripcion' => 'Compromiso activo',
        'fecha_inicio' => now()->addDay()->toDateString(),
        'fecha_fin' => now()->addDays(11)->toDateString(),
        'estado' => 'Pendiente',
        'activo' => true,
    ]);

    $this->patchJson('/api/compromisos-elementos/' . $commitment->compromiso_elemento_id . '/active', [
        'activo' => false,
    ])->assertStatus(200)
      ->assertJsonPath('success', true);

    $this->assertDatabaseHas('COMPROMISO_MEJORA_ELEMENTO', [
        'compromiso_elemento_id' => $commitment->compromiso_elemento_id,
        'activo' => 0,
    ]);
});

function featureElementCommitmentTreeBase(): array
{
    $model = StructureModel::factory()->create();
    $careerCampus = CareerCampus::factory()->create();
    $cycle = AccreditationCycle::factory()->create([
        'carrera_sede_id' => $careerCampus->carrera_sede_id,
        'modelo_estructura_id' => $model->modelo_estructura_id,
    ]);

    $process = Process::factory()->create([
        'ciclo_acreditacion_id' => $cycle->ciclo_acreditacion_id,
        'tipo_proceso' => 'Compromiso de mejora',
        'activo' => true,
    ]);

    $root = StructureElement::factory()->create([
        'modelo_estructura_id' => $model->modelo_estructura_id,
        'padre_id' => null,
        'activo' => true,
    ]);

    return [$process, $root];
}
