<?php

use App\Models\Component;
use App\Models\Dimension;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->baseEndpoint = '/api/estructura/componentes';
    $this->user = User::factory()->create();
    Sanctum::actingAs($this->user);
});

it('index devuelve lista de componentes', function () {
        Component::factory()->count(3)->create();

        $this->getJson($this->baseEndpoint)
             ->assertOk()
             ->assertJsonCount(3);
});

it('show devuelve un componente existente', function () {
        $dimension = Dimension::factory()->create();
        $component = Component::factory()->create([
            'dimension_id' => $dimension->getKey(),
            'nombre'       => 'Componente 2',
        ]);

        $this->getJson("{$this->baseEndpoint}/{$component->getKey()}")
             ->assertOk()
             ->assertJsonFragment([
                 'componente_id' => $component->getKey(),
                 'nombre'        => 'Componente 2',
             ]);
});

it('show devuelve 404 si no existe', function () {
        $this->getJson("{$this->baseEndpoint}/999999")->assertNotFound();
});

it('store crea un componente', function () {
        $dimension = Dimension::factory()->create();

        $requestPayload = [
            'dimension_id' => $dimension->getKey(),
            'nombre'       => 'Componente Alpha',
            'nomenclatura' => 'COMP-01',
        ];

        $this->postJson($this->baseEndpoint, $requestPayload)
             ->assertCreated()
             ->assertJsonFragment(['nombre' => 'Componente Alpha']);

        $this->assertDatabaseHas('COMPONENTE', [
            'dimension_id'  => $dimension->getKey(),
            'nombre'        => 'Componente Alpha',
            'nomenclatura'  => 'COMP-01',
        ]);
});
    
it('update actualiza un componente', function () {
        $dimension = Dimension::factory()->create();
        $component = Component::factory()->create([
            'dimension_id' => $dimension->getKey(),
            'nombre'       => 'Nombre Original',
        ]);

        $requestPayload = [
            'dimension_id' => $component->dimension_id,
            'nombre'       => 'Nombre Actualizado',
            'nomenclatura' => 'COMP-UPD',
        ];

        $this->putJson("{$this->baseEndpoint}/{$component->getKey()}", $requestPayload)
             ->assertOk()
             ->assertJsonFragment(['nombre' => 'Nombre Actualizado']);

        $this->assertDatabaseHas('COMPONENTE', [
            'componente_id' => $component->getKey(),
            'nombre'        => 'Nombre Actualizado',
        ]);
});

it('destroy elimina un componente', function () {
        $component = Component::factory()->create();

        $this->deleteJson("{$this->baseEndpoint}/{$component->getKey()}")
             ->assertNoContent();

        $this->assertDatabaseMissing('COMPONENTE', [
            'componente_id' => $component->getKey(),
        ]);
});

it('puede tener comentarios polimórficos', function () {
    $component = Component::factory()->create();
    $evidence = \App\Models\Evidence::factory()->create();
    
    $comment = \App\Models\Comment::factory()->for($component, 'commentable')->create([
        'texto' => 'Comentario en componente'
    ]);
    
    expect($component->comments)->toHaveCount(1);
    expect($component->comments->first()->texto)->toBe('Comentario en componente');
});
