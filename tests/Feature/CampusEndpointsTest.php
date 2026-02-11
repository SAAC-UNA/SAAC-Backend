<?php

use App\Models\Campus;
use App\Models\University;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->user = User::factory()->create();
    Sanctum::actingAs($this->user, ['web'], 'sanctum');
});

// AJUSTA según tus rutas: '/api/estructura/campus' o '/api/estructura/campuses'
$base = '/api/estructura/campuses';

it('index devuelve lista de campus', function () use ($base) {
    // Crea 3 campus (la factory debe crear University vía relación)
    Campus::factory()->count(3)->create();

    $this->getJson($base)
         ->assertOk()
         ->assertJsonCount(3);
});

it('show devuelve un campus existente', function () use ($base) {
    $c = Campus::factory()->create();

    $this->getJson($base.'/'.$c->getKey())
         ->assertOk()
         ->assertJsonFragment([
             'sede_id' => $c->getKey(),
             'nombre'  => $c->nombre,
         ]);
});

it('show devuelve 404 si no existe', function () use ($base) {
    $this->getJson($base.'/999999')->assertNotFound();
});

it('store crea un campus', function () use ($base) {
    $u = University::factory()->create();

    $data = [
        'universidad_id' => $u->getKey(),   // FK requerida
        'nombre'         => 'Campus Central',
    ];

    $this->postJson($base, $data)
         ->assertCreated()
         ->assertJsonFragment(['nombre' => 'Campus Central']);

    // OJO: tabla real es SEDE
    $this->assertDatabaseHas('SEDE', $data);
});

it('update actualiza un campus', function () use ($base) {
    $c = Campus::factory()->create(['nombre' => 'Original']);

    $payload = [
        'nombre' => 'Actualizado',
        'universidad_id' => $c->universidad_id, // 👈 este campo es obligatorio en tu request
    ];

    $this->putJson($base.'/'.$c->getKey(), $payload)
         ->assertOk()
         ->assertJsonFragment(['nombre' => 'Actualizado']);

    $this->assertDatabaseHas('SEDE', [
        'sede_id' => $c->getKey(),
        'nombre'  => 'Actualizado',
    ]);
});

it('destroy elimina un campus', function () use ($base) {
    $c = Campus::factory()->create();

    $this->deleteJson($base.'/'.$c->getKey())
         ->assertNoContent();

    $this->assertDatabaseMissing('SEDE', ['sede_id' => $c->getKey()]);
});
