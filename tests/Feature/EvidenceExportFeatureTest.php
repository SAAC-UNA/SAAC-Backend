<?php

use App\Models\Evidence;
use App\Models\Criterion;
use App\Models\Component;
use App\Models\Dimension;
use App\Models\EvidenceState;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->excelEndpoint = '/api/estructura/evidencias/export/excel';
    $this->pdfEndpoint = '/api/estructura/evidencias/export/pdf';

    // Crear usuario básico con rol Superusuario para evitar restricciones
    $this->user = User::factory()->create();
    
    // Crear rol Superusuario y asignárselo
    if (!Role::where('name', 'Superusuario')->where('guard_name', 'api')->exists()) {
        Role::create(['name' => 'Superusuario', 'guard_name' => 'api']);
    }
    $this->user->assignRole('Superusuario');
    $this->superUser = $this->user;

    // Configurar storage para tests
    Storage::fake('local');
});

it('export excel requiere autenticacion', function () {
    // Limpiar autenticación para probar que se requiere autenticación
    $this->app['auth']->forgetGuards();
    
    $response = $this->withHeaders([
        'Accept' => 'application/json',
        'Authorization' => ''
    ])->getJson($this->excelEndpoint);
    
    $response->assertUnauthorized();
});

it('export pdf requiere autenticacion', function () {
    // Limpiar autenticación para probar que se requiere autenticación
    $this->app['auth']->forgetGuards();
    
    $response = $this->withHeaders([
        'Accept' => 'application/json',
        'Authorization' => ''
    ])->getJson($this->pdfEndpoint);
    
    $response->assertUnauthorized();
});

it('export excel descarga archivo correctamente', function () {
    Sanctum::actingAs($this->superUser, ['web'], 'sanctum');

        // Crear datos de prueba
        $dimension = Dimension::factory()->create();
        $component = Component::factory()->create(['dimension_id' => $dimension->getKey()]);
        $criterion = Criterion::factory()->create(['componente_id' => $component->getKey()]);
        $state = EvidenceState::factory()->create();

        Evidence::factory()->count(3)->create([
            'criterio_id' => $criterion->getKey(),
            'estado_evidencia_id' => $state->getKey(),
        ]);

        $response = $this->getJson($this->excelEndpoint);

    $response->assertOk()
        ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
        ->assertDownload();
});

it('export pdf descarga archivo correctamente', function () {
    Sanctum::actingAs($this->superUser, ['web'], 'sanctum');

    // Crear datos de prueba
    $dimension = Dimension::factory()->create();
    $component = Component::factory()->create(['dimension_id' => $dimension->getKey()]);
    $criterion = Criterion::factory()->create(['componente_id' => $component->getKey()]);
    $state = EvidenceState::factory()->create();

    Evidence::factory()->count(3)->create([
        'criterio_id' => $criterion->getKey(),
        'estado_evidencia_id' => $state->getKey(),
    ]);

    $response = $this->getJson($this->pdfEndpoint);

    $response->assertOk()
        ->assertHeader('content-type', 'application/pdf')
        ->assertDownload();
});

it('export excel con filtro por criterio', function () {
    Sanctum::actingAs($this->superUser, ['web'], 'sanctum');

    $dimension = Dimension::factory()->create();
    $component = Component::factory()->create(['dimension_id' => $dimension->getKey()]);
    $criterion1 = Criterion::factory()->create(['componente_id' => $component->getKey()]);
    $criterion2 = Criterion::factory()->create(['componente_id' => $component->getKey()]);
    $state = EvidenceState::factory()->create();

    // 2 evidencias del criterio 1
    Evidence::factory()->count(2)->create([
        'criterio_id' => $criterion1->getKey(),
        'estado_evidencia_id' => $state->getKey(),
    ]);

    // 3 evidencias del criterio 2
    Evidence::factory()->count(3)->create([
        'criterio_id' => $criterion2->getKey(),
        'estado_evidencia_id' => $state->getKey(),
    ]);

    $response = $this->getJson($this->excelEndpoint . '?criterio_id=' . $criterion1->getKey());

    $response->assertOk()
        ->assertDownload();
});

it('export pdf con filtro por estado', function () {
    Sanctum::actingAs($this->superUser, ['web'], 'sanctum');

    $dimension = Dimension::factory()->create();
    $component = Component::factory()->create(['dimension_id' => $dimension->getKey()]);
    $criterion = Criterion::factory()->create(['componente_id' => $component->getKey()]);
    $state1 = EvidenceState::factory()->create(['nombre' => 'Pendiente']);
    $state2 = EvidenceState::factory()->create(['nombre' => 'Completado']);

    Evidence::factory()->count(2)->create([
        'criterio_id' => $criterion->getKey(),
        'estado_evidencia_id' => $state1->getKey(),
    ]);

    Evidence::factory()->count(3)->create([
        'criterio_id' => $criterion->getKey(),
        'estado_evidencia_id' => $state2->getKey(),
    ]);

    $response = $this->getJson($this->pdfEndpoint . '?estado_evidencia_id=' . $state1->getKey());

    $response->assertOk()
        ->assertDownload();
});

it('export excel con multiples filtros', function () {
    Sanctum::actingAs($this->superUser, ['web'], 'sanctum');

    $dimension = Dimension::factory()->create();
    $component = Component::factory()->create(['dimension_id' => $dimension->getKey()]);
    $criterion = Criterion::factory()->create(['componente_id' => $component->getKey()]);
    $state = EvidenceState::factory()->create();

    Evidence::factory()->count(5)->create([
        'criterio_id' => $criterion->getKey(),
        'estado_evidencia_id' => $state->getKey(),
    ]);

    $response = $this->getJson(
        $this->excelEndpoint . 
        '?criterio_id=' . $criterion->getKey() . 
        '&estado_evidencia_id=' . $state->getKey() .
        '&sort_by=nomenclatura&sort_order=asc'
    );

    $response->assertOk()
        ->assertDownload();
});

it('export excel sin evidencias descarga archivo vacio', function () {
    Sanctum::actingAs($this->superUser, ['web'], 'sanctum');

    // No crear evidencias

    $response = $this->getJson($this->excelEndpoint);

    $response->assertOk()
        ->assertDownload();
});

it('export pdf sin evidencias descarga archivo vacio', function () {
    Sanctum::actingAs($this->superUser, ['web'], 'sanctum');

    // No crear evidencias

    $response = $this->getJson($this->pdfEndpoint);

    $response->assertOk()
        ->assertDownload();
});

it('export excel valida parametros incorrectos', function () {
    Sanctum::actingAs($this->superUser, ['web'], 'sanctum');

    $response = $this->getJson($this->excelEndpoint . '?criterio_id=abc&per_page=abc');

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['criterio_id', 'per_page']);
});

it('export pdf valida parametros incorrectos', function () {
    Sanctum::actingAs($this->superUser, ['web'], 'sanctum');

    $response = $this->getJson($this->pdfEndpoint . '?estado_evidencia_id=xyz&sort_order=invalid');

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['estado_evidencia_id', 'sort_order']);
});
