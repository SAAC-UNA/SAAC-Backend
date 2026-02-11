<?php

use App\Models\Evidence;
use App\Models\Criterion;
use App\Models\Component;
use App\Models\Dimension;
use App\Models\EvidenceState;
use App\Models\User;
use App\Models\Process;
use App\Models\AccreditationCycle;
use App\Models\Career;
use App\Models\Campus;
use App\Models\University;
use App\Models\EvidenceAssignment;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->filterEndpoint = '/api/estructura/evidencias/filter';
    
    // Crear usuario básico con rol Superusuario para evitar restricciones de filtrado
    $this->user = User::factory()->create();
    
    // Crear rol Superusuario y asignárselo (necesario para el filtrado)
    if (!Role::where('name', 'Superusuario')->where('guard_name', 'api')->exists()) {
        Role::create(['name' => 'Superusuario', 'guard_name' => 'api']);
    }
    $this->user->assignRole('Superusuario');
    
    // Crear usuarios adicionales para pruebas específicas sin roles por ahora
    $this->superUser = $this->user; // Reutilizar el usuario principal
    $this->coordinador = User::factory()->create();  
    $this->evaluador = User::factory()->create();
    
    // Usar el usuario principal con rol Superusuario para autenticación por defecto
    Sanctum::actingAs($this->user, ['web'], 'sanctum');
});

it('filter requiere autenticacion', function () {
    // Limpiar autenticación para probar que se requiere autenticación
    $this->app['auth']->forgetGuards();
    
    $response = $this->withHeaders([
        'Accept' => 'application/json',
        'Authorization' => '' // Asegurarse de no enviar token
    ])->getJson($this->filterEndpoint);
    
    $response->assertUnauthorized();
});

it('filter devuelve todas las evidencias sin filtros', function () {

        // Crear estructura necesaria
        $dimension = Dimension::factory()->create();
        $component = Component::factory()->create(['dimension_id' => $dimension->getKey()]);
        $criterion = Criterion::factory()->create(['componente_id' => $component->getKey()]);
        $state = EvidenceState::factory()->create();

        // Crear evidencias
        Evidence::factory()->count(5)->create([
            'criterio_id' => $criterion->getKey(),
            'estado_evidencia_id' => $state->getKey(),
        ]);

        $response = $this->getJson($this->filterEndpoint);

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'evidencia_id',
                    'descripcion',
                    'nomenclatura',
                    'estado_evidencia',
                    'fecha_publicacion',
                ]
            ],
            'links',
            'meta'
        ])
        ->assertJsonCount(5, 'data');
});

it('filter por criterio devuelve evidencias correctas', function () {

        $dimension = Dimension::factory()->create();
        $component = Component::factory()->create(['dimension_id' => $dimension->getKey()]);
        $criterion1 = Criterion::factory()->create(['componente_id' => $component->getKey()]);
        $criterion2 = Criterion::factory()->create(['componente_id' => $component->getKey()]);
        $state = EvidenceState::factory()->create();

        // 3 evidencias del criterio 1
        Evidence::factory()->count(3)->create([
            'criterio_id' => $criterion1->getKey(),
            'estado_evidencia_id' => $state->getKey(),
        ]);

        // 2 evidencias del criterio 2
        Evidence::factory()->count(2)->create([
            'criterio_id' => $criterion2->getKey(),
            'estado_evidencia_id' => $state->getKey(),
        ]);

        $response = $this->getJson($this->filterEndpoint . '?criterio_id=' . $criterion1->getKey());

    $response->assertOk()
        ->assertJsonCount(3, 'data');
});

it('filter por estado devuelve evidencias correctas', function () {

        $dimension = Dimension::factory()->create();
        $component = Component::factory()->create(['dimension_id' => $dimension->getKey()]);
        $criterion = Criterion::factory()->create(['componente_id' => $component->getKey()]);
        $state1 = EvidenceState::factory()->create(['nombre' => 'Pendiente']);
        $state2 = EvidenceState::factory()->create(['nombre' => 'Completado']);

        Evidence::factory()->count(4)->create([
            'criterio_id' => $criterion->getKey(),
            'estado_evidencia_id' => $state1->getKey(),
        ]);

        Evidence::factory()->count(2)->create([
            'criterio_id' => $criterion->getKey(),
            'estado_evidencia_id' => $state2->getKey(),
        ]);

        $response = $this->getJson($this->filterEndpoint . '?estado_evidencia_id=' . $state1->getKey());

    $response->assertOk()
        ->assertJsonCount(4, 'data');
});

it('filter por responsable devuelve evidencias asignadas', function () {

        // Crear estructura
        $dimension = Dimension::factory()->create();
        $component = Component::factory()->create(['dimension_id' => $dimension->getKey()]);
        $criterion = Criterion::factory()->create(['componente_id' => $component->getKey()]);
        $state = EvidenceState::factory()->create();
        $university = University::factory()->create();
        $campus = Campus::factory()->create(['universidad_id' => $university->getKey()]);
        $career = Career::factory()->create();
        $career->campuses()->attach($campus->getKey());
        $careerCampus = \DB::table('CARRERA_SEDE')
            ->where('carrera_id', $career->getKey())
            ->where('sede_id', $campus->getKey())
            ->first();
        $cycle = AccreditationCycle::factory()->create(['carrera_sede_id' => $careerCampus->carrera_sede_id]);
        $process = Process::factory()->create(['ciclo_acreditacion_id' => $cycle->getKey()]);

        $evidence1 = Evidence::factory()->create([
            'criterio_id' => $criterion->getKey(),
            'estado_evidencia_id' => $state->getKey(),
        ]);

        $evidence2 = Evidence::factory()->create([
            'criterio_id' => $criterion->getKey(),
            'estado_evidencia_id' => $state->getKey(),
        ]);

        $user = User::factory()->create();

        // Asignar solo evidence1 al usuario
        EvidenceAssignment::factory()->create([
            'proceso_id' => $process->getKey(),
            'evidencia_id' => $evidence1->getKey(),
            'usuario_id' => $user->getKey(),
        ]);

        $response = $this->getJson($this->filterEndpoint . '?responsable_id=' . $user->getKey());

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.evidencia_id', $evidence1->getKey());
});

it('filter ordenamiento por nomenclatura ascendente', function () {

        $dimension = Dimension::factory()->create();
        $component = Component::factory()->create(['dimension_id' => $dimension->getKey()]);
        $criterion = Criterion::factory()->create(['componente_id' => $component->getKey()]);
        $state = EvidenceState::factory()->create();

        Evidence::factory()->create([
            'criterio_id' => $criterion->getKey(),
            'estado_evidencia_id' => $state->getKey(),
            'nomenclatura' => '23',
        ]);

        Evidence::factory()->create([
            'criterio_id' => $criterion->getKey(),
            'estado_evidencia_id' => $state->getKey(),
            'nomenclatura' => '20',
        ]);

        Evidence::factory()->create([
            'criterio_id' => $criterion->getKey(),
            'estado_evidencia_id' => $state->getKey(),
            'nomenclatura' => '21',
        ]);

        $response = $this->getJson($this->filterEndpoint . '?sort_by=nomenclatura&sort_order=asc');

    $response->assertOk()
        ->assertJsonPath('data.0.nomenclatura', '20')
        ->assertJsonPath('data.1.nomenclatura', '21')
        ->assertJsonPath('data.2.nomenclatura', '23');
});

it('filter paginacion funciona correctamente', function () {

        $dimension = Dimension::factory()->create();
        $component = Component::factory()->create(['dimension_id' => $dimension->getKey()]);
        $criterion = Criterion::factory()->create(['componente_id' => $component->getKey()]);
        $state = EvidenceState::factory()->create();

        Evidence::factory()->count(10)->create([
            'criterio_id' => $criterion->getKey(),
            'estado_evidencia_id' => $state->getKey(),
        ]);

        $response = $this->getJson($this->filterEndpoint . '?per_page=5');

    $response->assertOk()
        ->assertJsonCount(5, 'data')
        ->assertJsonPath('meta.per_page', 5)
        ->assertJsonPath('meta.total', 10);
});

it('superusuario ve todas las evidencias', function () {

        $dimension = Dimension::factory()->create();
        $component = Component::factory()->create(['dimension_id' => $dimension->getKey()]);
        $criterion = Criterion::factory()->create(['componente_id' => $component->getKey()]);
        $state = EvidenceState::factory()->create();

        Evidence::factory()->count(5)->create([
            'criterio_id' => $criterion->getKey(),
            'estado_evidencia_id' => $state->getKey(),
        ]);

        $response = $this->getJson($this->filterEndpoint);

    $response->assertOk()
        ->assertJsonCount(5, 'data');
});

it('evaluador solo ve evidencias asignadas', function () {
    Sanctum::actingAs($this->evaluador, ['web'], 'sanctum');

        // Crear estructura
        $dimension = Dimension::factory()->create();
        $component = Component::factory()->create(['dimension_id' => $dimension->getKey()]);
        $criterion = Criterion::factory()->create(['componente_id' => $component->getKey()]);
        $state = EvidenceState::factory()->create();
        $university = University::factory()->create();
        $campus = Campus::factory()->create(['universidad_id' => $university->getKey()]);
        $career = Career::factory()->create();
        $career->campuses()->attach($campus->getKey());
        $careerCampus = \DB::table('CARRERA_SEDE')
            ->where('carrera_id', $career->getKey())
            ->where('sede_id', $campus->getKey())
            ->first();
        $cycle = AccreditationCycle::factory()->create(['carrera_sede_id' => $careerCampus->carrera_sede_id]);
        $process = Process::factory()->create(['ciclo_acreditacion_id' => $cycle->getKey()]);

        $evidence1 = Evidence::factory()->create([
            'criterio_id' => $criterion->getKey(),
            'estado_evidencia_id' => $state->getKey(),
        ]);

        $evidence2 = Evidence::factory()->create([
            'criterio_id' => $criterion->getKey(),
            'estado_evidencia_id' => $state->getKey(),
        ]);

        // Solo asignar evidence1 al evaluador
        EvidenceAssignment::factory()->create([
            'proceso_id' => $process->getKey(),
            'evidencia_id' => $evidence1->getKey(),
            'usuario_id' => $this->evaluador->getKey(),
        ]);

        $response = $this->getJson($this->filterEndpoint);

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.evidencia_id', $evidence1->getKey());
});

it('filter multiples parametros combinados', function () {

        $dimension = Dimension::factory()->create();
        $component = Component::factory()->create(['dimension_id' => $dimension->getKey()]);
        $criterion1 = Criterion::factory()->create(['componente_id' => $component->getKey()]);
        $criterion2 = Criterion::factory()->create(['componente_id' => $component->getKey()]);
        $state1 = EvidenceState::factory()->create(['nombre' => 'Pendiente']);
        $state2 = EvidenceState::factory()->create(['nombre' => 'Completado']);

        Evidence::factory()->count(3)->create([
            'criterio_id' => $criterion1->getKey(),
            'estado_evidencia_id' => $state1->getKey(),
        ]);

        Evidence::factory()->count(2)->create([
            'criterio_id' => $criterion1->getKey(),
            'estado_evidencia_id' => $state2->getKey(),
        ]);

        Evidence::factory()->count(2)->create([
            'criterio_id' => $criterion2->getKey(),
            'estado_evidencia_id' => $state1->getKey(),
        ]);

        $response = $this->getJson($this->filterEndpoint . '?criterio_id=' . $criterion1->getKey() . '&estado_evidencia_id=' . $state1->getKey());

    $response->assertOk()
        ->assertJsonCount(3, 'data');
});
