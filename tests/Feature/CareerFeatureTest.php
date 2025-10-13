<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use App\Models\Career;
use App\Models\Faculty;

class CareerFeatureTest extends TestCase
{
    use RefreshDatabase;
     // AJUSTA si la ruta difiere
    private string $base = '/api/estructura/carreras';

   /* 
    public function it_can_create_and_retrieve_a_career()
    {
        $career = Career::factory()->create([
            'nombre' => 'Medicina',
        ]);

        $found = Career::where('nombre', 'Medicina')->first();
        $this->assertNotNull($found);
        $this->assertEquals('Medicina', $found->nombre);
    } */
   #[Test]
    public function index_devuelve_lista_de_carreras()
    {
        Career::factory()->count(3)->create();

        $this->getJson($this->base)
             ->assertOk()
             ->assertJsonCount(3);
    }

    #[Test]
    public function show_devuelve_una_carrera_existente()
    {
        $c = Career::factory()->create();

        $this->getJson("{$this->base}/{$c->getKey()}")
             ->assertOk()
             ->assertJsonFragment([
                 'carrera_id' => $c->getKey(),
                 'nombre'     => $c->nombre,
             ]);
    }

    #[Test]
    public function show_devuelve_404_si_no_existe()
    {
        $this->getJson("{$this->base}/999999")->assertNotFound();
    }

    #[Test]
    public function store_crea_una_carrera()
    {
        $fac = Faculty::factory()->create(); // FK requerida

        $data = [
            'facultad_id' => $fac->getKey(),
            'nombre'      => 'Ingeniería Industrial',
        ];

        $this->postJson($this->base, $data)
             ->assertCreated()
             ->assertJsonFragment(['nombre' => 'Ingeniería Industrial']);

        $this->assertDatabaseHas('CARRERA', $data);
    }

    #[Test]
    public function update_actualiza_una_carrera()
    {
        $c = Career::factory()->create(['nombre' => 'Original']);

        $payload = [
            'nombre'      => 'Actualizado',
            'facultad_id' => $c->facultad_id, // mantener FK obligatoria según tu Request
        ];

        $this->putJson("{$this->base}/{$c->getKey()}", $payload)
             ->assertOk()
             ->assertJsonFragment(['nombre' => 'Actualizado']);

        $this->assertDatabaseHas('CARRERA', [
            'carrera_id' => $c->getKey(),
            'nombre'     => 'Actualizado',
        ]);
    }

    #[Test]
    public function destroy_elimina_una_carrera()
    {
        $c = Career::factory()->create();

        $this->deleteJson("{$this->base}/{$c->getKey()}")
             ->assertNoContent();

        $this->assertDatabaseMissing('CARRERA', ['carrera_id' => $c->getKey()]);
    }
}
