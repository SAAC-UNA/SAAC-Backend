<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use App\Models\Component;
use App\Models\Dimension;
use App\Models\Comment;

class ComponentFeatureTest extends TestCase
{
    use RefreshDatabase;
      private string $baseEndpoint = '/api/estructura/componentes';
     #[Test]
    public function index_devuelve_lista_de_componentes()
    {
        Component::factory()->count(3)->create();

        $this->getJson($this->baseEndpoint)
             ->assertOk()
             ->assertJsonCount(3);
    }

    #[Test]
    public function show_devuelve_un_componente_existente()
    {
        $dimension = Dimension::factory()->create();
        $component = Component::factory()->create([
            'dimension_id' => $dimension->getKey(),
            'nombre'       => 'Componente 2',
        ]);

        $this->getJson("{$this->baseEndpoint}/{$component->getKey()}")
             ->assertOk()
             ->assertJsonFragment([
                 // Ajustá la clave si tu PK se llama distinto (p. ej. 'componente_id')
                 'componente_id' => $component->getKey(),
                 'nombre'        => 'Componente 2',
             ]);
    }

    #[Test]
    public function show_devuelve_404_si_no_existe()
    {
        $this->getJson("{$this->baseEndpoint}/999999")->assertNotFound();
    }

    #[Test]
    public function store_crea_un_componente()
    {
        $dimension = Dimension::factory()->create();
        $comentario = Comment::factory()->create();

        $requestPayload = [
            'dimension_id' => $dimension->getKey(),   // FK requerida
             'comentario_id' => $comentario->getKey(),
            'nombre'       => 'Componente Alpha',
            'nomenclatura' => 'COMP-01',  // requerido por tu Request
        ];

        $this->postJson($this->baseEndpoint, $requestPayload)
             ->assertCreated()
             ->assertJsonFragment(['nombre' => 'Componente Alpha']);

        // Ajustá el nombre real de tu tabla si es distinto (usual: 'COMPONENTE')
        $this->assertDatabaseHas('COMPONENTE', [
        'dimension_id'  => $dimension->getKey(),
        'comentario_id' => $comentario->getKey(),
        'nombre'        => 'Componente Alpha',
        'nomenclatura'  => 'COMP-01',
        ]);
    }
    
    #[Test]
    public function update_actualiza_un_componente()
    {
        $dimension = Dimension::factory()->create();
        $component = Component::factory()->create([
            'dimension_id' => $dimension->getKey(),
            'nombre'       => 'Nombre Original',
        ]);

        $requestPayload = [
            'dimension_id' => $component->dimension_id, // mantener FK si tu Request la exige
            'nombre'       => 'Nombre Actualizado',
        ];

        $this->putJson("{$this->baseEndpoint}/{$component->getKey()}", $requestPayload)
             ->assertOk()
             ->assertJsonFragment(['nombre' => 'Nombre Actualizado']);

        $this->assertDatabaseHas('COMPONENTE', [
            // Ajustá a tu PK real si es distinto
            'componente_id' => $component->getKey(),
            'nombre'        => 'Nombre Actualizado',
        ]);
    }

    #[Test]
    public function destroy_elimina_un_componente()
    {
        $component = Component::factory()->create();

        $this->deleteJson("{$this->baseEndpoint}/{$component->getKey()}")
             ->assertNoContent();

        $this->assertDatabaseMissing('COMPONENTE', [
            // Ajustá a tu PK real
            'componente_id' => $component->getKey(),
        ]);
    }

    // -------- Tests negativos (422) recomendados --------

    #[Test]
    public function store_falla_sin_campos_obligatorios()
    {
        $this->postJson($this->baseEndpoint, [])
             ->assertStatus(422)
             ->assertJsonStructure([
                 'message',
                 'errors' => ['nombre', 'dimension_id'],
             ]);
    }

    #[Test]
    public function store_falla_con_dimension_inexistente()
    {
        $requestPayload = [
            'dimension_id' => 999999,
            'nombre'       => 'Componente Z',
        ];

        $this->postJson($this->baseEndpoint, $requestPayload)
             ->assertStatus(422)
             ->assertJsonStructure([
                 'message',
                 'errors' => ['dimension_id'],
             ]);
    } 

    /*
    public function it_can_create_and_retrieve_a_component()
    {
        $dimension = \App\Models\Dimension::factory()->create();
        $component = Component::factory()->create([
            'dimension_id' => $dimension->dimension_id,
            'nombre' => 'Componente 2',
        ]);

        $found = Component::where('nombre', 'Componente 2')->where('dimension_id', $dimension->dimension_id)->first();
        $this->assertNotNull($found);
        $this->assertEquals('Componente 2', $found->nombre);
    }*/
}
