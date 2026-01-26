<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
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

class EvidenceFilterFeatureTest extends TestCase
{
    use RefreshDatabase;

    private string $filterEndpoint = '/api/estructura/evidencias/filter';
    private User $superUser;
    private User $coordinador;
    private User $evaluador;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear roles con guard 'api' para Sanctum
        Role::create(['name' => 'SuperUsuario', 'guard_name' => 'api']);
        Role::create(['name' => 'Coordinador', 'guard_name' => 'api']);
        Role::create(['name' => 'Evaluador', 'guard_name' => 'api']);

        // Crear usuarios con roles
        $this->superUser = User::factory()->create();
        $this->superUser->assignRole('SuperUsuario');

        $this->coordinador = User::factory()->create();
        $this->coordinador->assignRole('Coordinador');

        $this->evaluador = User::factory()->create();
        $this->evaluador->assignRole('Evaluador');
    }

    #[Test]
    public function filter_requiere_autenticacion()
    {
        $this->getJson($this->filterEndpoint)
            ->assertUnauthorized();
    }

    #[Test]
    public function filter_devuelve_todas_las_evidencias_sin_filtros()
    {
        Sanctum::actingAs($this->superUser);

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
    }

    #[Test]
    public function filter_por_criterio_devuelve_evidencias_correctas()
    {
        Sanctum::actingAs($this->superUser);

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
    }

    #[Test]
    public function filter_por_estado_devuelve_evidencias_correctas()
    {
        Sanctum::actingAs($this->superUser);

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
    }

    #[Test]
    public function filter_por_responsable_devuelve_evidencias_asignadas()
    {
        Sanctum::actingAs($this->superUser);

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
    }

    #[Test]
    public function filter_ordenamiento_por_nomenclatura_ascendente()
    {
        Sanctum::actingAs($this->superUser);

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
    }

    #[Test]
    public function filter_paginacion_funciona_correctamente()
    {
        Sanctum::actingAs($this->superUser);

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
    }

    #[Test]
    public function superusuario_ve_todas_las_evidencias()
    {
        Sanctum::actingAs($this->superUser);

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
    }

    #[Test]
    public function evaluador_solo_ve_evidencias_asignadas()
    {
        Sanctum::actingAs($this->evaluador);

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
    }

    #[Test]
    public function filter_multiples_parametros_combinados()
    {
        Sanctum::actingAs($this->superUser);

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
    }
}
