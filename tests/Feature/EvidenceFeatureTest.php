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

class EvidenceFeatureTest extends TestCase
{
    use RefreshDatabase;

    // AJUSTA si tu ruta difiere
    private string $baseEndpoint = '/api/estructura/evidencias';

    #[Test]
    public function index_devuelve_lista_de_evidencias()
    {
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
    }

    #[Test]
    public function show_devuelve_una_evidencia_existente()
    {
        $dimension = Dimension::factory()->create();
        $component = Component::factory()->create([
            'dimension_id' => $dimension->getKey(),
        ]);
        $criterion = Criterion::factory()->create([
            'componente_id' => $component->getKey(),
        ]);
        $evidenceState = EvidenceState::factory()->create();

        $evidence = Evidence::factory()->create([
            'criterio_id'         => $criterion->getKey(),
            'estado_evidencia_id' => $evidenceState->getKey(),
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
    }
    #[Test]
    public function store_crea_una_evidencia()
    {
        $dimension = Dimension::factory()->create();
        $component = Component::factory()->create([
            'dimension_id' => $dimension->getKey(),
        ]);
        $criterion = Criterion::factory()->create([
            'componente_id' => $component->getKey(),
        ]);
        $evidenceState = EvidenceState::factory()->create();

        $requestPayload = [
            'criterio_id'         => $criterion->getKey(),       // FK requerida
            'estado_evidencia_id' => $evidenceState->getKey(),   // FK requerida
            'descripcion'         => 'Nueva Evidencia',
            'nomenclatura'        => 'EVID-01',
        ];

        $this->postJson($this->baseEndpoint, $requestPayload)
             ->assertCreated()
             ->assertJsonPath('data.descripcion', 'Nueva Evidencia');

        $this->assertDatabaseHas('EVIDENCIA', [
            'criterio_id'         => $criterion->getKey(),
            'estado_evidencia_id' => $evidenceState->getKey(),
            'descripcion'         => 'Nueva Evidencia',
            'nomenclatura'        => 'EVID-01',
        ]);
    }

    #[Test]
    public function update_actualiza_una_evidencia()
    {
        $dimension = Dimension::factory()->create();
        $component = Component::factory()->create([
            'dimension_id' => $dimension->getKey(),
        ]);
        $criterion = Criterion::factory()->create([
            'componente_id' => $component->getKey(),
        ]);
        $evidenceState = EvidenceState::factory()->create();

        $evidence = Evidence::factory()->create([
            'criterio_id'         => $criterion->getKey(),
            'estado_evidencia_id' => $evidenceState->getKey(),
            'descripcion'         => 'Original',
            'nomenclatura'        => 'EVID-77',
        ]);

        $requestPayload = [
            'criterio_id'         => $evidence->criterio_id,           // mantener FKs si tu Request las exige
            'estado_evidencia_id' => $evidence->estado_evidencia_id,
            'descripcion'         => 'Actualizada',
            'nomenclatura'        => 'EVID-77X', // cambia para evitar choque con unique si lo tenés
        ];

        $this->putJson("{$this->baseEndpoint}/{$evidence->getKey()}", $requestPayload)
             ->assertOk()
             ->assertJsonPath('data.descripcion', 'Actualizada');

        $this->assertDatabaseHas('EVIDENCIA', [
            'evidencia_id' => $evidence->getKey(),
            'descripcion'  => 'Actualizada',
        ]);
    }

    #[Test]
    public function destroy_elimina_una_evidencia()
    {
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
    }

    // -------- Negativos (422) recomendados --------

    #[Test]
    public function store_falla_sin_campos_obligatorios()
    {
        $this->postJson($this->baseEndpoint, [])
             ->assertStatus(422)
             ->assertJsonStructure([
                 'message',
                 'errors' => ['criterio_id', 'estado_evidencia_id', 'descripcion', 'nomenclatura'],
             ]);
    }

    #[Test]
    public function store_falla_con_fks_inexistentes()
    {
        $requestPayload = [
            'criterio_id'         => 999999,
            'estado_evidencia_id' => 888888,
            'descripcion'         => 'Desc inválida',
            'nomenclatura'        => 'EVID-XX',
        ];

        $this->postJson($this->baseEndpoint, $requestPayload)
             ->assertStatus(422)
             ->assertJsonStructure([
                 'message',
                 'errors' => ['criterio_id', 'estado_evidencia_id'],
             ]);
    }
}
