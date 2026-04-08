<?php

use App\Models\AccreditationCycle;
use App\Models\Career;
use App\Models\Campus;
use App\Models\CareerCampus;
use App\Models\Component;
use App\Models\Criterion;
use App\Models\Dimension;
use App\Models\Evidence;
use App\Models\EvidenceAssignment;
use App\Models\Process;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;

// â”€â”€ helpers â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

function efEndpoint(): string
{
    return '/api/estructura/evidencias/filter';
}

function efAdmin()
{
    Artisan::call('db:seed', ['--class' => \Database\Seeders\RolesAndPermissionsSeeder::class]);
    $user = User::factory()->create();
    $user->assignRole(Role::findByName('Superusuario', 'api'));
    return $user;
}

function efCriterion(?int $compId = null)
{
    if ($compId) {
        return Criterion::factory()->create(['componente_id' => $compId]);
    }
    $dim  = Dimension::factory()->create();
    $comp = Component::factory()->create(['dimension_id' => $dim->dimension_id]);
    return Criterion::factory()->create(['componente_id' => $comp->componente_id]);
}

function efProcess()
{
    $cc    = CareerCampus::factory()->create([
        'carrera_id' => Career::factory()->create()->carrera_id,
        'sede_id'    => Campus::factory()->create()->sede_id,
    ]);
    $cycle = AccreditationCycle::factory()->create(['carrera_sede_id' => $cc->carrera_sede_id]);
    return Process::factory()->create(['ciclo_acreditacion_id' => $cycle->ciclo_acreditacion_id]);
}

// â”€â”€ tests â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

it('filter requiere autenticacion', function () {
    /** @var \Tests\TestCase $this */
    $response = $this->withHeaders(['Authorization' => ''])->getJson(efEndpoint());
    $response->assertUnauthorized();
});

it('filter devuelve todas las evidencias sin filtros', function () {
    /** @var \Tests\TestCase $this */
    $admin = efAdmin();
    Sanctum::actingAs($admin);
    $crit = efCriterion();
    Evidence::factory()->count(5)->create(['criterio_id' => $crit->criterio_id]);

    $this->getJson(efEndpoint())
        ->assertOk()
        ->assertJsonStructure(['data' => [['evidencia_id', 'descripcion', 'nomenclatura']], 'links', 'meta'])
        ->assertJsonCount(5, 'data');
});

it('filter por criterio devuelve evidencias correctas', function () {
    /** @var \Tests\TestCase $this */
    $admin = efAdmin();
    Sanctum::actingAs($admin);
    $dim   = Dimension::factory()->create();
    $comp  = Component::factory()->create(['dimension_id' => $dim->dimension_id]);
    $crit1 = efCriterion($comp->componente_id);
    $crit2 = efCriterion($comp->componente_id);
    Evidence::factory()->count(3)->create(['criterio_id' => $crit1->criterio_id]);
    Evidence::factory()->count(2)->create(['criterio_id' => $crit2->criterio_id]);

    $this->getJson(efEndpoint() . "?criterio_id={$crit1->criterio_id}")
        ->assertOk()
        ->assertJsonCount(3, 'data');
});

it('filter por estado devuelve evidencias correctas', function () {
    /** @var \Tests\TestCase $this */
    $admin = efAdmin();
    Sanctum::actingAs($admin);
    $crit = efCriterion();
    Evidence::factory()->count(4)->create(['criterio_id' => $crit->criterio_id, 'estado' => 'Pendiente']);
    Evidence::factory()->count(2)->create(['criterio_id' => $crit->criterio_id, 'estado' => 'Completado']);

    $this->getJson(efEndpoint() . '?estado=Pendiente')
        ->assertOk()
        ->assertJsonCount(4, 'data');
});

it('filter por responsable devuelve evidencias asignadas', function () {
    /** @var \Tests\TestCase $this */
    $admin = efAdmin();
    Sanctum::actingAs($admin);
    $crit    = efCriterion();
    $process = efProcess();
    $ev1     = Evidence::factory()->create(['criterio_id' => $crit->criterio_id]);
    Evidence::factory()->create(['criterio_id' => $crit->criterio_id]);
    $resp    = User::factory()->create();
    EvidenceAssignment::factory()->create([
        'proceso_id'   => $process->proceso_id,
        'evidencia_id' => $ev1->evidencia_id,
        'usuario_id'   => $resp->usuario_id,
    ]);

    $this->getJson(efEndpoint() . "?responsable_id={$resp->usuario_id}")
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.evidencia_id', $ev1->evidencia_id);
});

it('filter ordenamiento por nomenclatura ascendente', function () {
    /** @var \Tests\TestCase $this */
    $admin = efAdmin();
    Sanctum::actingAs($admin);
    $crit = efCriterion();
    foreach (['23', '20', '21'] as $n) {
        Evidence::factory()->create(['criterio_id' => $crit->criterio_id, 'nomenclatura' => $n]);
    }

    $this->getJson(efEndpoint() . '?sort_by=nomenclatura&sort_order=asc')
        ->assertOk()
        ->assertJsonPath('data.0.nomenclatura', '20')
        ->assertJsonPath('data.1.nomenclatura', '21')
        ->assertJsonPath('data.2.nomenclatura', '23');
});

it('filter paginacion funciona correctamente', function () {
    /** @var \Tests\TestCase $this */
    $admin = efAdmin();
    Sanctum::actingAs($admin);
    $crit = efCriterion();
    Evidence::factory()->count(10)->create(['criterio_id' => $crit->criterio_id]);

    $this->getJson(efEndpoint() . '?per_page=5')
        ->assertOk()
        ->assertJsonCount(5, 'data')
        ->assertJsonPath('meta.per_page', 5)
        ->assertJsonPath('meta.total', 10);
});

it('superusuario ve todas las evidencias', function () {
    /** @var \Tests\TestCase $this */
    $admin = efAdmin();
    Sanctum::actingAs($admin);
    $crit = efCriterion();
    Evidence::factory()->count(5)->create(['criterio_id' => $crit->criterio_id]);

    $this->getJson(efEndpoint())
        ->assertOk()
        ->assertJsonCount(5, 'data');
});

it('filter multiples parametros combinados', function () {
    /** @var \Tests\TestCase $this */
    $admin = efAdmin();
    Sanctum::actingAs($admin);
    $dim   = Dimension::factory()->create();
    $comp  = Component::factory()->create(['dimension_id' => $dim->dimension_id]);
    $crit1 = efCriterion($comp->componente_id);
    $crit2 = efCriterion($comp->componente_id);
    Evidence::factory()->count(3)->create(['criterio_id' => $crit1->criterio_id, 'estado' => 'Pendiente']);
    Evidence::factory()->count(2)->create(['criterio_id' => $crit1->criterio_id, 'estado' => 'Completado']);
    Evidence::factory()->count(2)->create(['criterio_id' => $crit2->criterio_id, 'estado' => 'Pendiente']);

    $this->getJson(efEndpoint() . "?criterio_id={$crit1->criterio_id}&estado=Pendiente")
        ->assertOk()
        ->assertJsonCount(3, 'data');
});
