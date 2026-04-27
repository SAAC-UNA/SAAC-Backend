<?php

use App\Models\Standard;
use App\Models\Criterion;
use App\Models\Component;
use App\Models\Dimension;
use App\Models\User;
use App\Models\Role;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->baseEndpoint = '/api/estructura/estandares';
    
    // Crear permisos necesarios (considerar tanto .create/.edit/.delete como .update)
    $permissions = [
        'estandares.view',
        'estandares.create',
        'estandares.edit',
        'estandares.update',
        'estandares.delete',
    ];
    
    foreach ($permissions as $perm) {
        \Spatie\Permission\Models\Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'api']);
    }
    
    // Crear rol y usuario autenticado
    $adminRole = Role::firstOrCreate(['name' => 'Administrador', 'guard_name' => 'api']);
    $adminRole->givePermissionTo($permissions);
    
    $this->user = User::factory()->create();
    $this->user->assignRole($adminRole);
    
    Sanctum::actingAs($this->user);
});

it('index devuelve lista de estandares', function () {
        $dimension = Dimension::factory()->create();
        $component = Component::factory()->create(['dimension_id' => $dimension->getKey()]);
        $criterion = Criterion::factory()->create(['componente_id' => $component->getKey()]);

        Standard::factory()->count(3)->create(['criterio_id' => $criterion->getKey()]);

        $resp = $this->getJson($this->baseEndpoint)->assertOk();
        $payload = $resp->json();
        $items = $payload['data'] ?? $payload;   // soporta con/sin wrapper

        $this->assertIsArray($items);
        $this->assertCount(3, $items);
});

it('show devuelve un estandar existente', function () {
        $dimension = Dimension::factory()->create();
        $component = Component::factory()->create(['dimension_id' => $dimension->getKey()]);
        $criterion = Criterion::factory()->create(['componente_id' => $component->getKey()]);

        $standard = Standard::factory()->create([
            'criterio_id' => $criterion->getKey(),
            'descripcion' => 'Estándar 2',
        ]);

        $resp = $this->getJson("{$this->baseEndpoint}/{$standard->getKey()}")->assertOk();
        $data = $resp->json('data') ?? $resp->json();

        $this->assertNotNull($data);
        $returnedId = $data['id'] ?? $data['estandar_id'] ?? null; // tolerante a id/estandar_id
        $this->assertSame($standard->getKey(), $returnedId);
        $this->assertSame('Estándar 2', $data['descripcion']);
});

it('show devuelve 404 si no existe', function () {
        $this->getJson("{$this->baseEndpoint}/999999")->assertNotFound();
});

it('store crea un estandar', function () {
        $dimension = Dimension::factory()->create();
        $component = Component::factory()->create(['dimension_id' => $dimension->getKey()]);
        $criterion = Criterion::factory()->create(['componente_id' => $component->getKey()]);

        $payload = [
            'criterio_id' => $criterion->getKey(), // FK requerida
            'descripcion' => 'Nuevo Estándar',
        ];

        $resp = $this->postJson($this->baseEndpoint, $payload)->assertCreated();
        $data = $resp->json('data') ?? $resp->json();
        $this->assertSame('Nuevo Estándar', $data['descripcion']);

        $this->assertDatabaseHas('ESTANDAR', [
            'criterio_id' => $criterion->getKey(),
            'descripcion' => 'Nuevo Estándar',
        ]);
});

it('update actualiza un estandar', function () {
        $dimension = Dimension::factory()->create();
        $component = Component::factory()->create(['dimension_id' => $dimension->getKey()]);
        $criterion = Criterion::factory()->create(['componente_id' => $component->getKey()]);

        $standard = Standard::factory()->create([
            'criterio_id' => $criterion->getKey(),
            'descripcion' => 'Original',
        ]);

        $payload = [
            'criterio_id' => $standard->criterio_id, // mantener FK si tu Request la exige
            'descripcion' => 'Actualizado',
        ];

        $resp = $this->putJson("{$this->baseEndpoint}/{$standard->getKey()}", $payload)->assertOk();
        $data = $resp->json('data') ?? $resp->json();
        $this->assertSame('Actualizado', $data['descripcion']);

        $this->assertDatabaseHas('ESTANDAR', [
            'estandar_id' => $standard->getKey(),
            'descripcion' => 'Actualizado',
        ]);
});

it('destroy elimina un estandar', function () {
        $dimension = Dimension::factory()->create();
        $component = Component::factory()->create(['dimension_id' => $dimension->getKey()]);
        $criterion = Criterion::factory()->create(['componente_id' => $component->getKey()]);
        $standard  = Standard::factory()->create(['criterio_id' => $criterion->getKey()]);

        $this->deleteJson("{$this->baseEndpoint}/{$standard->getKey()}")->assertNoContent();

        $this->assertDatabaseMissing('ESTANDAR', [
            'estandar_id' => $standard->getKey(),
        ]);
});

it('store falla sin campos obligatorios', function () {
        $response = $this->postJson($this->baseEndpoint, []);
        $response->assertUnprocessable()
                 ->assertJsonValidationErrors(['criterio_id', 'descripcion']);
});

it('store falla con criterio inexistente', function () {
        $payload = [
            'criterio_id' => 999999,
            'descripcion' => 'Desc inválida',
        ];

        $response = $this->postJson($this->baseEndpoint, $payload);
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['criterio_id']);
});
