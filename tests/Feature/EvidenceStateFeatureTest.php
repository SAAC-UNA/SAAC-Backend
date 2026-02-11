<?php

use App\Models\EvidenceState;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->baseEndpoint = '/api/estructura/estados-evidencia';
    $this->user = User::factory()->create();
    Sanctum::actingAs($this->user, ['web'], 'sanctum');
});

it('index devuelve lista de estados de evidencia', function () {
        EvidenceState::factory()->count(3)->create();

        $response = $this->getJson($this->baseEndpoint)->assertOk();

        // Tu API a veces envuelve en { data: [...] }. Nos adaptamos.
        $payload = $response->json();
        $items = $payload['data'] ?? $payload;

        $this->assertIsArray($items);
        $this->assertCount(3, $items);
});

it('show devuelve un estado de evidencia existente', function () {
        $evidenceState = EvidenceState::factory()->create([
            'nombre' => 'Estado 2',
        ]);

        $response = $this->getJson("{$this->baseEndpoint}/{$evidenceState->getKey()}")->assertOk();

        $data = $response->json('data') ?? $response->json(); // soporta con/sin wrapper
        $this->assertNotNull($data);

        // Acepta 'id' o 'estado_evidencia_id' como PK en el JSON
        $returnedId = $data['id'] ?? $data['estado_evidencia_id'] ?? null;
        $this->assertSame($evidenceState->getKey(), $returnedId);
        $this->assertSame('Estado 2', $data['nombre']);
});

it('show devuelve 404 si no existe', function () {
        $this->getJson("{$this->baseEndpoint}/999999")->assertNotFound();
});

it('store crea un estado de evidencia', function () {
        $requestPayload = ['nombre' => 'Nuevo Estado'];

        $response = $this->postJson($this->baseEndpoint, $requestPayload)->assertCreated();

        // Confirma en la respuesta
        $data = $response->json('data') ?? $response->json();
        $this->assertSame('Nuevo Estado', $data['nombre']);

        // Confirma en BD (ajusta el nombre real de la tabla si difiere)
        $this->assertDatabaseHas('ESTADO_EVIDENCIA', ['nombre' => 'Nuevo Estado']);
});

it('update actualiza un estado de evidencia', function () {
        $evidenceState = EvidenceState::factory()->create(['nombre' => 'Original']);

        $requestPayload = ['nombre' => 'Actualizado'];

        $response = $this->putJson("{$this->baseEndpoint}/{$evidenceState->getKey()}", $requestPayload)->assertOk();

        $data = $response->json('data') ?? $response->json();
        $this->assertSame('Actualizado', $data['nombre']);

        $this->assertDatabaseHas('ESTADO_EVIDENCIA', [
            'estado_evidencia_id' => $evidenceState->getKey(),
            'nombre'              => 'Actualizado',
        ]);
});

it('destroy elimina un estado de evidencia', function () {
        $evidenceState = EvidenceState::factory()->create();

        $this->deleteJson("{$this->baseEndpoint}/{$evidenceState->getKey()}")->assertNoContent();

        $this->assertDatabaseMissing('ESTADO_EVIDENCIA', [
            'estado_evidencia_id' => $evidenceState->getKey(),
        ]);
});

it('store falla sin nombre', function () {
         $this->postJson($this->baseEndpoint, [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['nombre']);
});
