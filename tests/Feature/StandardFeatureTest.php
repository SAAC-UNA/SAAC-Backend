<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use App\Models\Standard;
use App\Models\Criterion;
use App\Models\Component;
use App\Models\Dimension;

class StandardFeatureTest extends TestCase
{
    use RefreshDatabase;

    private string $baseEndpoint = '/api/estructura/estandares';

    #[Test]
    public function index_devuelve_lista_de_estandares()
    {
        $dimension = Dimension::factory()->create();
        $component = Component::factory()->create(['dimension_id' => $dimension->getKey()]);
        $criterion = Criterion::factory()->create(['componente_id' => $component->getKey()]);

        Standard::factory()->count(3)->create(['criterio_id' => $criterion->getKey()]);

        $resp = $this->getJson($this->baseEndpoint)->assertOk();
        $payload = $resp->json();
        $items = $payload['data'] ?? $payload;   // soporta con/sin wrapper

        $this->assertIsArray($items);
        $this->assertCount(3, $items);
    }

    #[Test]
    public function show_devuelve_un_estandar_existente()
    {
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
    }

    #[Test]
    public function show_devuelve_404_si_no_existe()
    {
        $this->getJson("{$this->baseEndpoint}/999999")->assertNotFound();
    }

    #[Test]
    public function store_crea_un_estandar()
    {
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
    }

    #[Test]
    public function update_actualiza_un_estandar()
    {
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
    }

    #[Test]
    public function destroy_elimina_un_estandar()
    {
        $dimension = Dimension::factory()->create();
        $component = Component::factory()->create(['dimension_id' => $dimension->getKey()]);
        $criterion = Criterion::factory()->create(['componente_id' => $component->getKey()]);
        $standard  = Standard::factory()->create(['criterio_id' => $criterion->getKey()]);

        $this->deleteJson("{$this->baseEndpoint}/{$standard->getKey()}")->assertNoContent();

        $this->assertDatabaseMissing('ESTANDAR', [
            'estandar_id' => $standard->getKey(),
        ]);
    }

    #[Test]
    public function store_falla_sin_campos_obligatorios()
    {
        $this->postJson($this->baseEndpoint, [])
             ->assertUnprocessable()
             ->assertJsonValidationErrors(['criterio_id', 'descripcion']);
    }

    #[Test]
    public function store_falla_con_criterio_inexistente()
    {
        $payload = [
            'criterio_id' => 999999,
            'descripcion' => 'Desc inválida',
        ];

        $this->postJson($this->baseEndpoint, $payload)
             ->assertStatus(422)
             ->assertJsonValidationErrors(['criterio_id']);
    }
}
