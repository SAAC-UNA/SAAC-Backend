<?php

use App\Models\University;
use App\Models\User;
use App\Models\Role;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    // Crear rol y usuario autenticado
    $adminRole = Role::create(['name' => 'Administrador', 'guard_name' => 'api']);
    $this->user = User::factory()->create();
    $this->user->assignRole($adminRole);
    
    Sanctum::actingAs($this->user);
});

it('index devuelve lista de universidades', function () {
        University::factory()->count(3)->create();

        $this->getJson('/api/estructura/universidades')
             ->assertOk()
             ->assertJsonCount(3);
});

it('show devuelve una universidad existente', function () {
        $u = University::factory()->create();

        $this->getJson('/api/estructura/universidades/'.$u->getKey())
             ->assertOk()
             ->assertJsonFragment(['nombre' => $u->nombre]);
});

it('show devuelve 404 si no existe', function () {
        $this->getJson('/api/estructura/universidades/999999')
             ->assertNotFound();
});

it('store crea una universidad', function () {
        $data = ['nombre' => 'Universidad Test'];

        $this->postJson('/api/estructura/universidades', $data)
             ->assertCreated()
             ->assertJsonFragment(['nombre' => 'Universidad Test']);

        $this->assertDatabaseHas('UNIVERSIDAD', $data);
});

it('update actualiza una universidad', function () {
        $u = University::factory()->create(['nombre' => 'Original']);

        $this->putJson('/api/estructura/universidades/'.$u->getKey(), [
            'nombre' => 'Actualizado'
        ])->assertOk()
          ->assertJsonFragment(['nombre' => 'Actualizado']);

        $this->assertDatabaseHas('UNIVERSIDAD', ['nombre' => 'Actualizado']);
});

it('destroy elimina una universidad', function () {
        $u = University::factory()->create();

        $this->deleteJson('/api/estructura/universidades/'.$u->getKey())
             ->assertNoContent();

        $this->assertDatabaseMissing('UNIVERSIDAD', ['universidad_id' => $u->getKey()]);
});
