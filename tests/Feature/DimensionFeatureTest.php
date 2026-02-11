<?php

use App\Models\Dimension;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->base = '/api/estructura/dimensiones';
    $this->user = User::factory()->create();
    Sanctum::actingAs($this->user);
});

it('index devuelve lista de dimensiones', function () {
        Dimension::factory()->count(3)->create();

        $this->getJson($this->base)
             ->assertOk()
             ->assertJsonCount(3);
});

it('show devuelve una dimension existente', function () {
        $d = Dimension::factory()->create(['nombre' => 'Dimensión 2']);

        $this->getJson("{$this->base}/{$d->getKey()}")
             ->assertOk()
             ->assertJsonFragment([
                 'dimension_id' => $d->getKey(),
                 'nombre'       => 'Dimensión 2',
             ]);
});

it('show devuelve 404 si no existe', function () {
        $this->getJson("{$this->base}/999999")->assertNotFound();
});

it('store crea una dimension', function () {
        $data = [
            'nombre'        => 'Nueva Dimensión',
            'nomenclatura'  => 'DIM-01',
        ];

        $this->postJson($this->base, $data)
             ->assertCreated()
             ->assertJsonFragment(['nombre' => 'Nueva Dimensión']);

        $this->assertDatabaseHas('DIMENSION', $data);
});

it('update actualiza una dimension', function () {
        $d = Dimension::factory()->create(['nombre' => 'Original']);
        
        $payload = [
            'nombre'        => 'Actualizada',
            'nomenclatura'  => $d->nomenclatura.'-X',
        ];
        
        $this->putJson("{$this->base}/{$d->getKey()}", $payload)
             ->assertOk()
             ->assertJsonFragment(['nombre' => 'Actualizada']);

        $this->assertDatabaseHas('DIMENSION', [
            'dimension_id' => $d->getKey(),
            'nombre'       => 'Actualizada',
        ]);
});

it('destroy elimina una dimension', function () {
        $d = Dimension::factory()->create();

        $this->deleteJson("{$this->base}/{$d->getKey()}")
             ->assertNoContent();

        $this->assertDatabaseMissing('DIMENSION', ['dimension_id' => $d->getKey()]);
});
