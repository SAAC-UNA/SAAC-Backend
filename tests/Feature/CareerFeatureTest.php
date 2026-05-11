<?php

use App\Models\Career;
use App\Models\User;
use App\Models\Campus;
use App\Models\University;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->base = '/api/estructura/carreras';
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    $this->user = User::factory()->create();
    $this->user->assignRole(Role::where('name', 'Superusuario')->where('guard_name', 'api')->first());
    Sanctum::actingAs($this->user);
});

it('index devuelve lista de carreras', function () {
        Career::factory()->count(3)->create();

        $this->getJson($this->base)
             ->assertOk()
             ->assertJsonCount(3);
});

it('show devuelve una carrera existente', function () {
        $c = Career::factory()->create();

        $this->getJson("{$this->base}/{$c->getKey()}")
             ->assertOk()
             ->assertJsonFragment([
                 'carrera_id' => $c->getKey(),
                 'nombre'     => $c->nombre,
             ]);
});

it('show devuelve 404 si no existe', function () {
        $this->getJson("{$this->base}/999999")->assertNotFound();
});

it('store crea una carrera', function () {
        $university = University::factory()->create();

        $data = [
            'nombre'         => 'Ingeniería Industrial',
            'universidad_id' => $university->universidad_id,
        ];

        $this->postJson($this->base, $data)
             ->assertStatus(201)
             ->assertJsonPath('data.nombre', 'Ingeniería Industrial');

        $this->assertDatabaseHas('CARRERA', [
            'nombre' => 'Ingeniería Industrial',
            'universidad_id' => $university->universidad_id,
        ]);
});

it('update actualiza una carrera', function () {
        $university = University::factory()->create();
        $c = Career::factory()->create([
            'nombre' => 'Original',
            'universidad_id' => $university->universidad_id,
        ]);

        $payload = [
            'nombre' => 'Actualizado',
            'universidad_id' => $university->universidad_id,
        ];

        $this->putJson("{$this->base}/{$c->getKey()}", $payload)
             ->assertOk()
             ->assertJsonFragment(['nombre' => 'Actualizado']);

        $this->assertDatabaseHas('CARRERA', [
            'carrera_id' => $c->getKey(),
            'nombre'     => 'Actualizado',
        ]);
});

it('destroy elimina una carrera', function () {
        $c = Career::factory()->create();

        $this->deleteJson("{$this->base}/{$c->getKey()}")
             ->assertNoContent();

        $this->assertDatabaseMissing('CARRERA', ['carrera_id' => $c->getKey()]);
});
