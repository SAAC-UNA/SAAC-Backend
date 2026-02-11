<?php

use App\Models\Criterion;
use App\Models\Component;
use App\Models\Dimension;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->baseEndpoint = '/api/estructura/criterios';
    $this->user = User::factory()->create();
    Sanctum::actingAs($this->user);
});

it('index devuelve lista de criterios', function () {
        $dimension = Dimension::factory()->create();
        $component = Component::factory()->create([
            'dimension_id' => $dimension->getKey(),
        ]);

        // La factory debe poblar 'nomenclatura' y 'comentario_id'
        Criterion::factory()->count(3)->create([
            'componente_id' => $component->getKey(),
        ]);

        $this->getJson($this->baseEndpoint)
             ->assertOk()
             // tu API devuelve { data: [...] }
             ->assertJsonCount(3, 'data');
});

it('show devuelve un criterio existente', function () {
        $dimension = Dimension::factory()->create();
        $component = Component::factory()->create([
            'dimension_id' => $dimension->getKey(),
        ]);
        $criterion = Criterion::factory()->create([
            'componente_id' => $component->getKey(),
            'descripcion'   => 'Criterio 2',
        ]);

        $this->getJson("{$this->baseEndpoint}/{$criterion->getKey()}")
             ->assertOk()
             // tu API envuelve en data y usa 'id' como PK en el JSON
             ->assertJsonPath('data.id', $criterion->getKey())
             ->assertJsonPath('data.descripcion', 'Criterio 2');
});

it('show devuelve 404 si no existe', function () {
        $this->getJson("{$this->baseEndpoint}/999999")->assertNotFound();
});

it('store crea un criterio', function () {
        $dimension = Dimension::factory()->create();
        $component = Component::factory()->create([
            'dimension_id' => $dimension->getKey(),
        ]);

        $requestPayload = [
            'componente_id' => $component->getKey(),
            'descripcion'   => 'Nuevo Criterio',
            'nomenclatura'  => 'CRIT-01',
        ];

        $this->postJson($this->baseEndpoint, $requestPayload)
             ->assertCreated()
             ->assertJsonPath('data.descripcion', 'Nuevo Criterio');

        $this->assertDatabaseHas('CRITERIO', [
            'componente_id' => $component->getKey(),
            'descripcion'   => 'Nuevo Criterio',
            'nomenclatura'  => 'CRIT-01',
        ]);
});

it('update actualiza un criterio', function () {
        $dimension = Dimension::factory()->create();
        $component = Component::factory()->create([
            'dimension_id' => $dimension->getKey(),
        ]);
        $criterion = Criterion::factory()->create([
            'componente_id' => $component->getKey(),
            'descripcion'   => 'Original',
        ]);

        $requestPayload = [
            'componente_id' => $criterion->componente_id,
            'descripcion'   => 'Actualizado',
            'nomenclatura'  => $criterion->nomenclatura,
        ];

        $this->putJson("{$this->baseEndpoint}/{$criterion->getKey()}", $requestPayload)
             ->assertOk()
             ->assertJsonPath('data.descripcion', 'Actualizado');

        $this->assertDatabaseHas('CRITERIO', [
            'criterio_id' => $criterion->getKey(),
            'descripcion' => 'Actualizado',
        ]);
});

it('destroy elimina un criterio', function () {
        $dimension = Dimension::factory()->create();
        $component = Component::factory()->create([
            'dimension_id' => $dimension->getKey(),
        ]);
        $criterion = Criterion::factory()->create([
            'componente_id' => $component->getKey(),
        ]);

        $this->deleteJson("{$this->baseEndpoint}/{$criterion->getKey()}")
             ->assertNoContent();

        $this->assertDatabaseMissing('CRITERIO', [
            'criterio_id' => $criterion->getKey(),
        ]);
});

it('store falla sin campos obligatorios', function () {
        $this->postJson($this->baseEndpoint, [])
             ->assertStatus(422)
             ->assertJsonStructure([
                 'message',
                 'errors' => ['descripcion', 'componente_id'],
             ]);
});

it('store falla con componente inexistente', function () {
        $requestPayload = [
            'componente_id' => 999999,
            'descripcion'   => 'Desc inválida',
        ];

        $this->postJson($this->baseEndpoint, $requestPayload)
             ->assertStatus(422)
             ->assertJsonStructure([
                 'message',
                 'errors' => ['componente_id'],
             ]);
});
