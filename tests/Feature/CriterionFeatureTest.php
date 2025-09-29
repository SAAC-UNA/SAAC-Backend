<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use App\Models\Criterion;
use App\Models\Component;
use App\Models\Dimension;
use App\Models\Comment; // o: use App\Models\Comentario as Comment;

class CriterionFeatureTest extends TestCase
{
    use RefreshDatabase;

    // AJUSTA si tu ruta difiere
    private string $baseEndpoint = '/api/estructura/criterios';

    #[Test]
    public function index_devuelve_lista_de_criterios()
    {
        $dimension = Dimension::factory()->create();
        $component = Component::factory()->create([
            'dimension_id' => $dimension->getKey(),
        ]);

        // La factory debe poblar 'nomenclatura' y 'comentario_id'
        Criterion::factory()->count(3)->create([
            'componente_id' => $component->getKey(),
        ]);

        $this->getJson($this->baseEndpoint)
             ->assertOk()
             // tu API devuelve { data: [...] }
             ->assertJsonCount(3, 'data');
    }

    #[Test]
    public function show_devuelve_un_criterio_existente()
    {
        $dimension = Dimension::factory()->create();
        $component = Component::factory()->create([
            'dimension_id' => $dimension->getKey(),
        ]);
        $criterion = Criterion::factory()->create([
            'componente_id' => $component->getKey(),
            'descripcion'   => 'Criterio 2',
        ]);

        $this->getJson("{$this->baseEndpoint}/{$criterion->getKey()}")
             ->assertOk()
             // tu API envuelve en data y usa 'id' como PK en el JSON
             ->assertJsonPath('data.id', $criterion->getKey())
             ->assertJsonPath('data.descripcion', 'Criterio 2');
    }

    #[Test]
    public function show_devuelve_404_si_no_existe()
    {
        $this->getJson("{$this->baseEndpoint}/999999")->assertNotFound();
    }

    #[Test]
    public function store_crea_un_criterio()
    {
        $dimension = Dimension::factory()->create();
        $component = Component::factory()->create([
            'dimension_id' => $dimension->getKey(),
        ]);
        $comment = Comment::factory()->create(); // si 'comentario_id' es requerido

        $requestPayload = [
            'componente_id' => $component->getKey(), // FK requerida
            'descripcion'   => 'Nuevo Criterio',
            'nomenclatura'  => 'CRIT-01',            // requerido por tu Request
            'comentario_id' => $comment->getKey(),   // si tu schema lo exige
        ];

        $this->postJson($this->baseEndpoint, $requestPayload)
             ->assertCreated()
             ->assertJsonPath('data.descripcion', 'Nuevo Criterio');

        // Ajusta el nombre de tabla si difiere
        $this->assertDatabaseHas('CRITERIO', [
            'componente_id' => $component->getKey(),
            'descripcion'   => 'Nuevo Criterio',
            'nomenclatura'  => 'CRIT-01',
            'comentario_id' => $comment->getKey(),
        ]);
    }

    #[Test]
    public function update_actualiza_un_criterio()
    {
        $dimension = Dimension::factory()->create();
        $component = Component::factory()->create([
            'dimension_id' => $dimension->getKey(),
        ]);
        $criterion = Criterion::factory()->create([
            'componente_id' => $component->getKey(),
            'descripcion'   => 'Original',
        ]);

        $requestPayload = [
            'componente_id' => $criterion->componente_id,
            'descripcion'   => 'Actualizado',
            // si tu Request exige 'nomenclatura' y/o 'comentario_id' en update,
            // incluye aquí los actuales:
            'nomenclatura'  => $criterion->nomenclatura ?? 'CRIT-XX',
            'comentario_id' => $criterion->comentario_id ?? Comment::factory()->create()->getKey(),
        ];

        $this->putJson("{$this->baseEndpoint}/{$criterion->getKey()}", $requestPayload)
             ->assertOk()
             ->assertJsonPath('data.descripcion', 'Actualizado');

        $this->assertDatabaseHas('CRITERIO', [
            'criterio_id' => $criterion->getKey(),
            'descripcion' => 'Actualizado',
        ]);
    }

    #[Test]
    public function destroy_elimina_un_criterio()
    {
        $dimension = Dimension::factory()->create();
        $component = Component::factory()->create([
            'dimension_id' => $dimension->getKey(),
        ]);
        $criterion = Criterion::factory()->create([
            'componente_id' => $component->getKey(),
        ]);

        $this->deleteJson("{$this->baseEndpoint}/{$criterion->getKey()}")
             ->assertNoContent();

        $this->assertDatabaseMissing('CRITERIO', [
            'criterio_id' => $criterion->getKey(),
        ]);
    }

    // -------- Negativos (422) ya los tenés, los dejo tal cual --------

    #[Test]
    public function store_falla_sin_campos_obligatorios()
    {
        $this->postJson($this->baseEndpoint, [])
             ->assertStatus(422)
             ->assertJsonStructure([
                 'message',
                 'errors' => ['descripcion', 'componente_id'],
             ]);
    }

    #[Test]
    public function store_falla_con_componente_inexistente()
    {
        $requestPayload = [
            'componente_id' => 999999,
            'descripcion'   => 'Desc inválida',
        ];

        $this->postJson($this->baseEndpoint, $requestPayload)
             ->assertStatus(422)
             ->assertJsonStructure([
                 'message',
                 'errors' => ['componente_id'],
             ]);
    }
}
