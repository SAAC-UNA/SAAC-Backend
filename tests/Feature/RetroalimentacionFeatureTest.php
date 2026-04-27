<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Role;
use App\Models\Evidence;
use App\Models\Criterion;
use App\Models\Component;
use App\Models\Dimension;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class RetroalimentacionFeatureTest extends TestCase
{
    use RefreshDatabase;

    private string $baseEndpoint = '/api/estructura/evidencias';
    private User $encargado;

    private function retryTransientDb(callable $callback, int $attempts = 3)
    {
        $lastException = null;

        for ($attempt = 1; $attempt <= $attempts; $attempt++) {
            try {
                return $callback();
            } catch (\Illuminate\Database\QueryException $exception) {
                $message = strtolower($exception->getMessage());
                $isTransient = str_contains($message, 'table definition has changed')
                    || str_contains($message, 'deadlock found');

                if (!$isTransient || $attempt === $attempts) {
                    throw $exception;
                }

                $lastException = $exception;
            }
        }

        if ($lastException) {
            throw $lastException;
        }

        return null;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        // Encargado autorizado: necesita evidencias.edit para pasar el middleware de la ruta
        $this->encargado = User::factory()->create();
        $this->encargado->assignRole(Role::where('name', 'Encargado de Acreditación')->first());
        $this->encargado->givePermissionTo(
            Permission::findByName('evidencias.edit', 'api')
        );
    }

    // ─── Helper: crea evidencia en un estado revisable ───────────────────────

    private function crearEvidenciaRevisable(string $estado = 'En Proceso'): Evidence
    {
        $dimension = Dimension::factory()->create();
        $component = Component::factory()->create(['dimension_id' => $dimension->getKey()]);
        $criterion = Criterion::factory()->create(['componente_id' => $component->getKey()]);

        return Evidence::factory()->create([
            'criterio_id' => $criterion->getKey(),
            'estado'      => $estado,
        ]);
    }

    // ─── AC-5: Control de acceso ─────────────────────────────────────────────

    public function test_retorna_401_si_no_hay_token_de_autenticacion(): void
    {
        $evidence = $this->crearEvidenciaRevisable();

        $this->postJson("{$this->baseEndpoint}/{$evidence->getKey()}/retroalimentacion", [
            'estado'     => 'Observada',
            'comentario' => 'Comentario de prueba válido.',
        ])->assertStatus(401);
    }

    public function test_retorna_403_si_el_usuario_tiene_rol_Profesor(): void
    {
        $profesor = User::factory()->create();
        $profesor->assignRole(Role::where('name', 'Profesor')->first());
        Sanctum::actingAs($profesor);

        $evidence = $this->crearEvidenciaRevisable();

        $this->postJson("{$this->baseEndpoint}/{$evidence->getKey()}/retroalimentacion", [
            'estado'     => 'Observada',
            'comentario' => 'Intento no autorizado.',
        ])->assertStatus(403);
    }

    // ─── 404 ─────────────────────────────────────────────────────────────────

    public function test_retorna_404_cuando_la_evidencia_no_existe(): void
    {
        Sanctum::actingAs($this->encargado);

        $this->postJson("{$this->baseEndpoint}/999999/retroalimentacion", [
            'estado'     => 'Observada',
            'comentario' => 'Comentario para evidencia inexistente.',
        ])->assertStatus(404);
    }

    // ─── AC-2: Validación de campos ──────────────────────────────────────────

    public function test_retorna_422_cuando_el_comentario_esta_vacio(): void
    {
        Sanctum::actingAs($this->encargado);
        $evidence = $this->crearEvidenciaRevisable();

        $this->postJson("{$this->baseEndpoint}/{$evidence->getKey()}/retroalimentacion", [
            'estado'     => 'Observada',
            'comentario' => '',
        ])->assertStatus(422)
          ->assertJsonValidationErrors(['comentario']);
    }

    public function test_retorna_422_cuando_el_comentario_tiene_menos_de_5_caracteres(): void
    {
        Sanctum::actingAs($this->encargado);
        $evidence = $this->crearEvidenciaRevisable();

        $this->postJson("{$this->baseEndpoint}/{$evidence->getKey()}/retroalimentacion", [
            'estado'     => 'Observada',
            'comentario' => 'Hi',
        ])->assertStatus(422)
          ->assertJsonValidationErrors(['comentario']);
    }

    public function test_retorna_422_cuando_el_comentario_supera_los_800_caracteres(): void
    {
        Sanctum::actingAs($this->encargado);
        $evidence = $this->crearEvidenciaRevisable();

        $this->postJson("{$this->baseEndpoint}/{$evidence->getKey()}/retroalimentacion", [
            'estado'     => 'Observada',
            'comentario' => str_repeat('A', 801),
        ])->assertStatus(422)
          ->assertJsonValidationErrors(['comentario']);
    }

    public function test_retorna_422_cuando_el_estado_no_esta_entre_Observada_y_Validada(): void
    {
        Sanctum::actingAs($this->encargado);
        $evidence = $this->crearEvidenciaRevisable();

        $this->postJson("{$this->baseEndpoint}/{$evidence->getKey()}/retroalimentacion", [
            'estado'     => 'Aprobado',
            'comentario' => 'Estado que no debería aceptarse.',
        ])->assertStatus(422)
          ->assertJsonValidationErrors(['estado']);
    }

    public function test_retorna_422_cuando_la_evidencia_esta_en_estado_Pendiente(): void
    {
        Sanctum::actingAs($this->encargado);
        $evidence = $this->crearEvidenciaRevisable('Pendiente');

        $this->postJson("{$this->baseEndpoint}/{$evidence->getKey()}/retroalimentacion", [
            'estado'     => 'Observada',
            'comentario' => 'No se puede retroalimentar si está pendiente.',
        ])->assertStatus(422);
    }

    // ─── Flujo feliz: 200 OK ─────────────────────────────────────────────────

    public function test_encargado_puede_marcar_evidencia_como_Observada_y_guarda_comentario(): void
    {
        Sanctum::actingAs($this->encargado);
        $evidence = $this->crearEvidenciaRevisable('En Proceso');

        $response = $this->postJson("{$this->baseEndpoint}/{$evidence->getKey()}/retroalimentacion", [
            'estado'     => 'Observada',
            'comentario' => 'Falta la firma del director en el acta.',
        ])->assertStatus(200);

        $response->assertJsonPath('data.estado', 'Observada');

        $this->assertDatabaseHas('EVIDENCIA', [
            'evidencia_id' => $evidence->getKey(),
            'estado'       => 'Observada',
        ]);

        $this->assertDatabaseHas('COMENTARIO', [
            'texto'      => 'Falta la firma del director en el acta.',
            'usuario_id' => $this->encargado->usuario_id,
        ]);
    }

    public function test_encargado_puede_marcar_evidencia_como_Validada_y_guarda_comentario(): void
    {
        Sanctum::actingAs($this->encargado);
        $evidence = $this->crearEvidenciaRevisable('En Proceso');

        $this->postJson("{$this->baseEndpoint}/{$evidence->getKey()}/retroalimentacion", [
            'estado'     => 'Validada',
            'comentario' => 'Evidencia revisada y aprobada. Cumple todos los requisitos.',
        ])->assertStatus(200)
          ->assertJsonPath('data.estado', 'Validada');

        $this->assertDatabaseHas('EVIDENCIA', [
            'evidencia_id' => $evidence->getKey(),
            'estado'       => 'Validada',
        ]);
    }

    public function test_la_respuesta_incluye_el_comentario_recien_creado_en_el_array_de_comentarios(): void
    {
        Sanctum::actingAs($this->encargado);
        $evidence = $this->crearEvidenciaRevisable('Completado');

        $response = $this->postJson("{$this->baseEndpoint}/{$evidence->getKey()}/retroalimentacion", [
            'estado'     => 'Observada',
            'comentario' => 'Se requiere adjuntar evidencia fotográfica.',
        ])->assertStatus(200);

        $comentarios = $response->json('data.comentarios');
        $this->assertNotNull($comentarios);
        $this->assertGreaterThanOrEqual(1, count($comentarios));

        $textos = array_column($comentarios, 'texto');
        $this->assertContains('Se requiere adjuntar evidencia fotográfica.', $textos);
    }

    public function test_administrador_tambien_puede_retroalimentar_evidencias(): void
    {
        $super = User::factory()->create();
        $super->assignRole(Role::where('name', 'Superusuario')->first());
        Sanctum::actingAs($super);

        $evidence = $this->crearEvidenciaRevisable('Vencido');

        $response = $this->retryTransientDb(fn() => $this->postJson("{$this->baseEndpoint}/{$evidence->getKey()}/retroalimentacion", [
            'estado'     => 'Observada',
            'comentario' => 'Evidencia vencida con observaciones.',
        ]));

        $response->assertStatus(200);
    }
}
