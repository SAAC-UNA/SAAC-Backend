<?php

use App\Models\Evidence;
use App\Models\Criterion;
use App\Models\Component;
use App\Models\Dimension;
use App\Models\EvidenceState;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->baseEndpoint = '/api/estructura/evidencias';
    $this->user = User::factory()->create();
    Sanctum::actingAs($this->user, ['web'], 'sanctum');
});

it('index devuelve lista de evidencias', function () {
        $dimension = Dimension::factory()->create();
        $component = Component::factory()->create([
            'dimension_id' => $dimension->getKey(),
        ]);
        $criterion = Criterion::factory()->create([
            'componente_id' => $component->getKey(),
        ]);

        Evidence::factory()->count(3)->create([
            'criterio_id' => $criterion->getKey(),
        ]);

        $this->getJson($this->baseEndpoint)
             ->assertOk()
             // si tu API envuelve en { data: [...] }
             ->assertJsonCount(3, 'data');
});

it('show devuelve una evidencia existente', function () {
        $dimension = Dimension::factory()->create();
        $component = Component::factory()->create([
            'dimension_id' => $dimension->getKey(),
        ]);
        $criterion = Criterion::factory()->create([
            'componente_id' => $component->getKey(),
        ]);
        $evidenceState = EvidenceState::factory()->create();

        $evidence = Evidence::factory()->create([
            'criterio_id'         => $criterion->getKey(),
            'estado_evidencia_id' => $evidenceState->getKey(),
            'descripcion'         => 'Evidencia 2',
            'nomenclatura'        => 'EVID-22',
        ]);

        $response = $this->getJson("{$this->baseEndpoint}/{$evidence->getKey()}")->assertOk();

        $data = $response->json('data');
        $this->assertNotNull($data, 'La respuesta no trae "data".');

         // Acepta 'id' o 'evidencia_id'
        $returnedId = $data['id'] ?? $data['evidencia_id'] ?? null;
        $this->assertSame($evidence->getKey(), $returnedId, 'El ID en la respuesta no coincide.');

        $this->assertSame('Evidencia 2', $data['descripcion']);
});

it('store crea una evidencia', function () {
        $dimension = Dimension::factory()->create();
        $component = Component::factory()->create([
            'dimension_id' => $dimension->getKey(),
        ]);
        $criterion = Criterion::factory()->create([
            'componente_id' => $component->getKey(),
        ]);
        $evidenceState = EvidenceState::factory()->create();

        $requestPayload = [
            'criterio_id'         => $criterion->getKey(),       // FK requerida
            'estado_evidencia_id' => $evidenceState->getKey(),   // FK requerida
            'descripcion'         => 'Nueva Evidencia',
            'nomenclatura'        => 'EVID-01',
        ];

        $this->postJson($this->baseEndpoint, $requestPayload)
             ->assertCreated()
             ->assertJsonPath('data.descripcion', 'Nueva Evidencia');

        $this->assertDatabaseHas('EVIDENCIA', [
            'criterio_id'         => $criterion->getKey(),
            'estado_evidencia_id' => $evidenceState->getKey(),
            'descripcion'         => 'Nueva Evidencia',
            'nomenclatura'        => 'EVID-01',
        ]);
});

it('update actualiza una evidencia', function () {
        $dimension = Dimension::factory()->create();
        $component = Component::factory()->create([
            'dimension_id' => $dimension->getKey(),
        ]);
        $criterion = Criterion::factory()->create([
            'componente_id' => $component->getKey(),
        ]);
        $evidenceState = EvidenceState::factory()->create();

        $evidence = Evidence::factory()->create([
            'criterio_id'         => $criterion->getKey(),
            'estado_evidencia_id' => $evidenceState->getKey(),
            'descripcion'         => 'Original',
            'nomenclatura'        => 'EVID-77',
        ]);

        $requestPayload = [
            'criterio_id'         => $evidence->criterio_id,           // mantener FKs si tu Request las exige
            'estado_evidencia_id' => $evidence->estado_evidencia_id,
            'descripcion'         => 'Actualizada',
            'nomenclatura'        => 'EVID-77X', // cambia para evitar choque con unique si lo tenés
        ];

        $this->putJson("{$this->baseEndpoint}/{$evidence->getKey()}", $requestPayload)
             ->assertOk()
             ->assertJsonPath('data.descripcion', 'Actualizada');

        $this->assertDatabaseHas('EVIDENCIA', [
            'evidencia_id' => $evidence->getKey(),
            'descripcion'  => 'Actualizada',
        ]);
});

it('destroy elimina una evidencia', function () {
        $dimension = Dimension::factory()->create();
        $component = Component::factory()->create([
            'dimension_id' => $dimension->getKey(),
        ]);
        $criterion = Criterion::factory()->create([
            'componente_id' => $component->getKey(),
        ]);

        $evidence = Evidence::factory()->create([
            'criterio_id' => $criterion->getKey(),
        ]);

        $this->deleteJson("{$this->baseEndpoint}/{$evidence->getKey()}")
             ->assertNoContent();

        $this->assertDatabaseMissing('EVIDENCIA', [
            'evidencia_id' => $evidence->getKey(),
        ]);
});

it('store falla sin campos obligatorios', function () {
        $this->postJson($this->baseEndpoint, [])
             ->assertStatus(422)
             ->assertJsonStructure([
                 'message',
                 'errors' => ['criterio_id', 'estado_evidencia_id', 'descripcion', 'nomenclatura'],
             ]);
});

it('store falla con fks inexistentes', function () {
        $requestPayload = [
            'criterio_id'         => 999999,
            'estado_evidencia_id' => 888888,
            'descripcion'         => 'Desc inválida',
            'nomenclatura'        => 'EVID-XX',
        ];

        $this->postJson($this->baseEndpoint, $requestPayload)
             ->assertStatus(422)
             ->assertJsonStructure([
                 'message',
                 'errors' => ['criterio_id', 'estado_evidencia_id'],
             ]);
});
