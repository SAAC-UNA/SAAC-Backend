<?php

use App\Models\Evidence;
use App\Models\Criterion;
use App\Models\Component;
use App\Models\Dimension;
use App\Models\AuditLog;
use App\Models\File;
use App\Models\Process;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->excelEndpoint = '/api/estructura/evidencias/export/excel';
    $this->pdfEndpoint = '/api/estructura/evidencias/export/pdf';

    // Crear usuario básico con rol Superusuario para evitar restricciones
    $this->user = User::factory()->create();
    
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    // Crear rol Superusuario y asignárselo
    if (!Role::where('name', 'Superusuario')->where('guard_name', 'api')->exists()) {
        Role::create(['name' => 'Superusuario', 'guard_name' => 'api']);
    }
    $this->user->assignRole('Superusuario');
    $this->superUser = $this->user;

    $this->seed(\Database\Seeders\ActionTypeSeeder::class);

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
        $process = Process::factory()->create();
        $responsable = User::factory()->create();

        $evidence = Evidence::factory()->create([
            'criterio_id' => $criterion->getKey(),
            'estado' => 'Pendiente',
        ]);

        File::create([
            'evidencia_id' => $evidence->getKey(),
            'usuario_id' => $responsable->getKey(),
            'proceso_id' => $process->getKey(),
            'fecha_subida' => now(),
            'tipo' => 'enlace',
            'path' => null,
            'url' => 'https://example.com/evidence-1',
            'nombre_original' => 'evidence-1',
            'tamanio' => null,
            'tipo_mime' => null,
            'is_publico' => false,
            'token_publico' => null,
            'link_expira_en' => null,
        ]);

        $evidence->load(['criterion.component.dimension', 'assignments.user', 'files']);

        $response = $this->getJson($this->excelEndpoint);

    $response->assertOk()
        ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
        ->assertDownload();

    expect(AuditLog::where('usuario_id', $this->superUser->usuario_id)
        ->where('modulo', 'Reportes')
        ->whereHas('actionType', fn ($query) => $query->where('descripcion', 'exportar'))
        ->count())->toBe(1);
});

it('export pdf descarga archivo correctamente', function () {
    Sanctum::actingAs($this->superUser, ['web'], 'sanctum');

    // Crear datos de prueba
    $dimension = Dimension::factory()->create();
    $component = Component::factory()->create(['dimension_id' => $dimension->getKey()]);
    $criterion = Criterion::factory()->create(['componente_id' => $component->getKey()]);

    Evidence::factory()->count(3)->create([
        'criterio_id' => $criterion->getKey(),
        'estado' => 'Pendiente',
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

    // 2 evidencias del criterio 1
    Evidence::factory()->count(2)->create([
        'criterio_id' => $criterion1->getKey(),
        'estado' => 'Pendiente',
    ]);

    // 3 evidencias del criterio 2
    Evidence::factory()->count(3)->create([
        'criterio_id' => $criterion2->getKey(),
        'estado' => 'Pendiente',
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

    Evidence::factory()->count(2)->create([
        'criterio_id' => $criterion->getKey(),
        'estado' => 'Pendiente',
    ]);

    Evidence::factory()->count(3)->create([
        'criterio_id' => $criterion->getKey(),
        'estado' => 'Completado',
    ]);

    $response = $this->getJson($this->pdfEndpoint . '?estado=Pendiente');

    $response->assertOk()
        ->assertDownload();
});

it('export excel con multiples filtros', function () {
    Sanctum::actingAs($this->superUser, ['web'], 'sanctum');

    $dimension = Dimension::factory()->create();
    $component = Component::factory()->create(['dimension_id' => $dimension->getKey()]);
    $criterion = Criterion::factory()->create(['componente_id' => $component->getKey()]);

    Evidence::factory()->count(5)->create([
        'criterio_id' => $criterion->getKey(),
        'estado' => 'Pendiente',
    ]);

    $response = $this->getJson(
        $this->excelEndpoint . 
        '?criterio_id=' . $criterion->getKey() . 
        '&estado=Pendiente' .
        '&sort_by=nomenclatura&sort_order=asc'
    );

    $response->assertOk()
        ->assertDownload();
});

it('export excel sin evidencias descarga archivo vacio', function () {
    Sanctum::actingAs($this->superUser, ['web'], 'sanctum');

    $response = $this->getJson($this->excelEndpoint);

    $response->assertOk()
        ->assertDownload();
});

it('export pdf sin evidencias descarga archivo vacio', function () {
    Sanctum::actingAs($this->superUser, ['web'], 'sanctum');

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

    $response = $this->getJson($this->pdfEndpoint . '?estado=xyz&sort_order=invalid');

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['estado', 'sort_order']);
});
