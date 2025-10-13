<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test; 
use App\Models\Faculty;
use App\Models\Campus;

class FacultyFeatureTest extends TestCase
{
    use RefreshDatabase;

     // AJUSTA: '/api/estructura/facultades' o '/api/estructura/faculties'
    private string $base = '/api/estructura/facultades';
    # [test]
     public function index_devuelve_lista_de_facultades()
    {
        Faculty::factory()->count(3)->create();
        $this->getJson($this->base)
             ->assertOk()
             ->assertJsonCount(3);
    }
    /*public function it_can_create_and_retrieve_a_faculty()
    {
        $faculty = Faculty::factory()->create([
            'nombre' => 'Facultad de Letras',
        ]);

        $found = Faculty::where('nombre', 'Facultad de Letras')->first();
        $this->assertNotNull($found);
        $this->assertEquals('Facultad de Letras', $found->nombre);
    }*/
     #[Test]
    public function show_devuelve_una_facultad_existente()
    {
        $f = Faculty::factory()->create();
        $this->getJson($this->base.'/'.$f->getKey())
             ->assertOk()
             ->assertJsonFragment([
                 'facultad_id' => $f->getKey(),
                 'nombre'      => $f->nombre,
             ]);
    }

    #[Test]
    public function show_devuelve_404_si_no_existe()
    {
        $this->getJson($this->base.'/999999')->assertNotFound();
    }

    #[Test]
    public function store_crea_una_facultad()
    {
        $campus = Campus::factory()->create(); // Campus ya tiene universidad_id

        $data = [
            'sede_id'        => $campus->sede_id,
            'universidad_id' => $campus->universidad_id,  //  agregar
            'nombre'         => 'Facultad de Letras',
        ];

        $this->postJson($this->base, $data)
            ->assertCreated()
             ->assertJsonFragment(['nombre' => 'Facultad de Letras']);

        $this->assertDatabaseHas('FACULTAD', [
            'sede_id'        => $campus->sede_id,
            'universidad_id' => $campus->universidad_id,
            'nombre'         => 'Facultad de Letras',
        ]);
    }

    #[Test]
    public function update_actualiza_una_facultad()
    {
        $f = Faculty::factory()->create(['nombre' => 'Original']);

        $payload = [
            'nombre'  => 'Actualizado',
            'sede_id' => $f->sede_id, // mantener FK obligatoria en tu Request
            'universidad_id' => $f->universidad_id, // mantener FK obligatoria en tu Request
        ];

        $this->putJson($this->base.'/'.$f->getKey(), $payload)
             ->assertOk()
             ->assertJsonFragment(['nombre' => 'Actualizado']);

        $this->assertDatabaseHas('FACULTAD', [
            'facultad_id' => $f->getKey(),
            'nombre'      => 'Actualizado',
        ]);
    }

    #[Test]
    public function destroy_elimina_una_facultad()
    {
        $f = Faculty::factory()->create();

        $this->deleteJson($this->base.'/'.$f->getKey())
             ->assertNoContent();

        $this->assertDatabaseMissing('FACULTAD', ['facultad_id' => $f->getKey()]);
    }
}
