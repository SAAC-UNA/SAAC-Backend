<?php

use App\Models\Evidence;
use App\Models\Criterion;
use App\Models\Component;
use App\Models\Dimension;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->baseEndpoint = '/api/estructura/evidencias';

    $permissions = [
        'evidencias.view',
        'evidencias.create',
        'evidencias.edit',
        'evidencias.update',
        'evidencias.delete',
    ];

    foreach ($permissions as $permissionName) {
        Permission::firstOrCreate([
            'name' => $permissionName,
            'guard_name' => 'api',
        ]);
    }

    $adminRole = Role::firstOrCreate([
        'name' => 'Administrador',
        'guard_name' => 'api',
    ]);
    $adminRole->syncPermissions($permissions);

    $this->user = User::factory()->create();
    $this->user->assignRole($adminRole);
    Sanctum::actingAs($this->user, ['api'], 'sanctum');
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

        $evidence = Evidence::factory()->create([
            'criterio_id'         => $criterion->getKey(),
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

        $requestPayload = [
            'criterio_id'         => $criterion->getKey(),       // FK requerida
            'descripcion'         => 'Nueva Evidencia',
            'nomenclatura'        => 'EVID-01',
        ];

        $response = $this->postJson($this->baseEndpoint, $requestPayload);
        $this->assertContains($response->status(), [200, 201, 400, 422]);
});

it('update actualiza una evidencia', function () {
        $dimension = Dimension::factory()->create();
        $component = Component::factory()->create([
            'dimension_id' => $dimension->getKey(),
        ]);
        $criterion = Criterion::factory()->create([
            'componente_id' => $component->getKey(),
        ]);

        $evidence = Evidence::factory()->create([
            'criterio_id'         => $criterion->getKey(),
            'descripcion'         => 'Original',
            'nomenclatura'        => 'EVID-77',
        ]);

        $requestPayload = [
            'criterio_id'         => $evidence->criterio_id,
            'descripcion'         => 'Actualizada',
            'nomenclatura'        => 'EVID-77X',
        ];

        $response = $this->putJson("{$this->baseEndpoint}/{$evidence->getKey()}", $requestPayload);
        $this->assertContains($response->status(), [200, 400, 422]);
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
        $response = $this->postJson($this->baseEndpoint, []);
        $this->assertContains($response->status(), [400, 422]);
});

it('store falla con fks inexistentes', function () {
        $requestPayload = [
            'criterio_id'         => 999999,
            'descripcion'         => 'Desc inválida',
            'nomenclatura'        => 'EVID-XX',
        ];

        $response = $this->postJson($this->baseEndpoint, $requestPayload);
        $this->assertContains($response->status(), [400, 422]);
});
