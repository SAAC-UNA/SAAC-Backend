<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use App\Models\EvidenceState;

class EvidenceStateFeatureTest extends TestCase
{
    use RefreshDatabase;

    // AJUSTA si tu ruta difiere (p. ej. '/api/estructura/estados-evidencia')
    private string $baseEndpoint = '/api/estructura/estados-evidencia';

    #[Test]
    public function index_devuelve_lista_de_estados_de_evidencia()
    {
        EvidenceState::factory()->count(3)->create();

        $response = $this->getJson($this->baseEndpoint)->assertOk();

        // Tu API a veces envuelve en { data: [...] }. Nos adaptamos.
        $payload = $response->json();
        $items = $payload['data'] ?? $payload;

        $this->assertIsArray($items);
        $this->assertCount(3, $items);
    }

    #[Test]
    public function show_devuelve_un_estado_de_evidencia_existente()
    {
        $evidenceState = EvidenceState::factory()->create([
            'nombre' => 'Estado 2',
        ]);

        $response = $this->getJson("{$this->baseEndpoint}/{$evidenceState->getKey()}")->assertOk();

        $data = $response->json('data') ?? $response->json(); // soporta con/sin wrapper
        $this->assertNotNull($data);

        // Acepta 'id' o 'estado_evidencia_id' como PK en el JSON
        $returnedId = $data['id'] ?? $data['estado_evidencia_id'] ?? null;
        $this->assertSame($evidenceState->getKey(), $returnedId);
        $this->assertSame('Estado 2', $data['nombre']);
    }

    #[Test]
    public function show_devuelve_404_si_no_existe()
    {
        $this->getJson("{$this->baseEndpoint}/999999")->assertNotFound();
    }

    #[Test]
    public function store_crea_un_estado_de_evidencia()
    {
        $requestPayload = ['nombre' => 'Nuevo Estado'];

        $response = $this->postJson($this->baseEndpoint, $requestPayload)->assertCreated();

        // Confirma en la respuesta
        $data = $response->json('data') ?? $response->json();
        $this->assertSame('Nuevo Estado', $data['nombre']);

        // Confirma en BD (ajusta el nombre real de la tabla si difiere)
        $this->assertDatabaseHas('ESTADO_EVIDENCIA', ['nombre' => 'Nuevo Estado']);
    }

    #[Test]
    public function update_actualiza_un_estado_de_evidencia()
    {
        $evidenceState = EvidenceState::factory()->create(['nombre' => 'Original']);

        $requestPayload = ['nombre' => 'Actualizado'];

        $response = $this->putJson("{$this->baseEndpoint}/{$evidenceState->getKey()}", $requestPayload)->assertOk();

        $data = $response->json('data') ?? $response->json();
        $this->assertSame('Actualizado', $data['nombre']);

        $this->assertDatabaseHas('ESTADO_EVIDENCIA', [
            'estado_evidencia_id' => $evidenceState->getKey(),
            'nombre'              => 'Actualizado',
        ]);
    }

    #[Test]
    public function destroy_elimina_un_estado_de_evidencia()
    {
        $evidenceState = EvidenceState::factory()->create();

        $this->deleteJson("{$this->baseEndpoint}/{$evidenceState->getKey()}")->assertNoContent();

        $this->assertDatabaseMissing('ESTADO_EVIDENCIA', [
            'estado_evidencia_id' => $evidenceState->getKey(),
        ]);
    }

    // -------- Negativos (422) recomendados --------

    #[Test]
    public function store_falla_sin_nombre()
    {
         $this->postJson($this->baseEndpoint, [])
        ->assertUnprocessable()                // equivale a status 422
        ->assertJsonValidationErrors(['nombre']);
    }
}
