<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\University;
use PHPUnit\Framework\Attributes\Test;

class UniversityEndpointsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function index_devuelve_lista_de_universidades()
    {
        University::factory()->count(3)->create();

        $this->getJson('/api/estructura/universidades')
             ->assertOk()
             ->assertJsonCount(3);
    }

    #[Test]
    public function show_devuelve_una_universidad_existente()
    {
        $u = University::factory()->create();

        $this->getJson('/api/estructura/universidades/'.$u->getKey())
             ->assertOk()
             ->assertJsonFragment(['nombre' => $u->nombre]);
    }

    #[Test]
    public function show_devuelve_404_si_no_existe()
    {
        $this->getJson('/api/estructura/universidades/999999')
             ->assertNotFound();
    }

    #[Test]
    public function store_crea_una_universidad()
    {
        $data = ['nombre' => 'Universidad Test'];

        $this->postJson('/api/estructura/universidades', $data)
             ->assertCreated()
             ->assertJsonFragment(['nombre' => 'Universidad Test']);

        $this->assertDatabaseHas('UNIVERSIDAD', $data);
    }

    #[Test]
    public function update_actualiza_una_universidad()
    {
        $u = University::factory()->create(['nombre' => 'Original']);

        $this->putJson('/api/estructura/universidades/'.$u->getKey(), [
            'nombre' => 'Actualizado'
        ])->assertOk()
          ->assertJsonFragment(['nombre' => 'Actualizado']);

        $this->assertDatabaseHas('UNIVERSIDAD', ['nombre' => 'Actualizado']);
    }

    #[Test]
    public function destroy_elimina_una_universidad()
    {
        $u = University::factory()->create();

        $this->deleteJson('/api/estructura/universidades/'.$u->getKey())
             ->assertNoContent();

        $this->assertDatabaseMissing('UNIVERSIDAD', ['universidad_id' => $u->getKey()]);
    }
}
