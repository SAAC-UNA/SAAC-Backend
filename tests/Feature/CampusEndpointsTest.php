<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Campus;
use App\Models\University;
use PHPUnit\Framework\Attributes\Test;

class CampusEndpointsTest extends TestCase
{
    use RefreshDatabase;

    // AJUSTA según tus rutas: '/api/estructura/campus' o '/api/estructura/campuses'
    private string $base = '/api/estructura/campuses';

    #[Test]
    public function index_devuelve_lista_de_campus()
    {
        // Crea 3 campus (la factory debe crear University vía relación)
        Campus::factory()->count(3)->create();

        $this->getJson($this->base)
             ->assertOk()
             ->assertJsonCount(3);
    }

    #[Test]
    public function show_devuelve_un_campus_existente()
    {
        $c = Campus::factory()->create();

        $this->getJson($this->base.'/'.$c->getKey())
             ->assertOk()
             ->assertJsonFragment([
                 'sede_id' => $c->getKey(),
                 'nombre'  => $c->nombre,
             ]);
    }

    #[Test]
    public function show_devuelve_404_si_no_existe()
    {
        $this->getJson($this->base.'/999999')->assertNotFound();
    }

    #[Test]
    public function store_crea_un_campus()
    {
        $u = University::factory()->create();

        $data = [
            'universidad_id' => $u->getKey(),   // FK requerida
            'nombre'         => 'Campus Central',
        ];

        $this->postJson($this->base, $data)
             ->assertCreated()
             ->assertJsonFragment(['nombre' => 'Campus Central']);

        // OJO: tabla real es SEDE
        $this->assertDatabaseHas('SEDE', $data);
    }

    #[Test]
public function update_actualiza_un_campus()
{
    $c = Campus::factory()->create(['nombre' => 'Original']);

    $payload = [
        'nombre' => 'Actualizado',
        'universidad_id' => $c->universidad_id, // 👈 este campo es obligatorio en tu request
    ];

    $this->putJson($this->base.'/'.$c->getKey(), $payload)
         ->assertOk()
         ->assertJsonFragment(['nombre' => 'Actualizado']);

    $this->assertDatabaseHas('SEDE', [
        'sede_id' => $c->getKey(),
        'nombre'  => 'Actualizado',
    ]);
}

    #[Test]
    public function destroy_elimina_un_campus()
    {
        $c = Campus::factory()->create();

        $this->deleteJson($this->base.'/'.$c->getKey())
             ->assertNoContent();

        $this->assertDatabaseMissing('SEDE', ['sede_id' => $c->getKey()]);
    }
}
