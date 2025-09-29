<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use App\Models\Dimension;
use App\Models\Comment; 

class DimensionFeatureTest extends TestCase
{
    use RefreshDatabase;
    private string $base = '/api/estructura/dimensiones';

    
    /*public function it_can_create_and_retrieve_a_dimension()
    {
        $dimension = Dimension::factory()->create([
            'nombre' => 'Dimensión 2',
        ]);

        $found = Dimension::where('nombre', 'Dimensión 2')->first();
        $this->assertNotNull($found);
        $this->assertEquals('Dimensión 2', $found->nombre);
    }*/
         #[Test]
    public function index_devuelve_lista_de_dimensiones()
    {
        Dimension::factory()->count(3)->create();

        $this->getJson($this->base)
             ->assertOk()
             ->assertJsonCount(3);
    }

    #[Test]
    public function show_devuelve_una_dimension_existente()
    {
        $d = Dimension::factory()->create(['nombre' => 'Dimensión 2']);

        $this->getJson("{$this->base}/{$d->getKey()}")
             ->assertOk()
             ->assertJsonFragment([
                 'dimension_id' => $d->getKey(),
                 'nombre'       => 'Dimensión 2',
             ]);
    }

    #[Test]
    public function show_devuelve_404_si_no_existe()
    {
        $this->getJson("{$this->base}/999999")->assertNotFound();
    }

    #[Test]
    public function store_crea_una_dimension()
    {
        $comentario = Comment::factory()->create();
        $data = [
            'nombre'        => 'Nueva Dimensión',
            'nomenclatura'  => 'DIM-01',             //  requerido por tu Request
            'comentario_id' => $comentario->getKey(),     //  requerido por tu Request
        ];

        $this->postJson($this->base, $data)
             ->assertCreated()
             ->assertJsonFragment(['nombre' => 'Nueva Dimensión']);

        $this->assertDatabaseHas('DIMENSION', $data);
    }

    #[Test]
    public function update_actualiza_una_dimension()
    {
        $d = Dimension::factory()->create(['nombre' => 'Original']);
        

        $payload = [
            'nombre'        => 'Actualizada',
            'nomenclatura'  => $d->nomenclatura.'-X', // distinta para no chocar con unique
            'comentario_id' => $d->comentario_id,
        ];
        
        $this->putJson("{$this->base}/{$d->getKey()}", $payload)
             ->assertOk()
             ->assertJsonFragment(['nombre' => 'Actualizada']);

        $this->assertDatabaseHas('DIMENSION', [
            'dimension_id' => $d->getKey(),
            'nombre'       => 'Actualizada',
        ]);
    }

    #[Test]
    public function destroy_elimina_una_dimension()
    {
        $d = Dimension::factory()->create();

        $this->deleteJson("{$this->base}/{$d->getKey()}")
             ->assertNoContent();

        $this->assertDatabaseMissing('DIMENSION', ['dimension_id' => $d->getKey()]);
    }
}
