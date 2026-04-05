<?php

namespace Tests\Feature;

use App\Models\AccreditationCycle;
use App\Models\CareerCampus;
use App\Models\ElementApproval;
use App\Models\Process;
use App\Models\Role;
use App\Models\StructureElement;
use App\Models\StructureModel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Tests de Feature para HU-010: Aprobación de Elementos (modelo flexible).
 * Rutas: /api/aprobaciones-elementos, /api/elementos/{id}/aprobar, /api/elementos/{id}/rechazar
 */
class ElementApprovalFeatureTest extends TestCase
{
    use RefreshDatabase;

    private User $superusuario;
    private Process $proceso;
    private StructureElement $elemento;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        // Superusuario tiene todos los permisos (aprobaciones.approve, aprobaciones.reject, aprobaciones.view)
        $this->superusuario = User::factory()->create();
        $this->superusuario->assignRole(Role::where('name', 'Superusuario')->first());

        // Estructura mínima: modelo → ciclo → proceso + elemento del mismo modelo
        $modelo             = StructureModel::factory()->create();
        $careerCampus       = CareerCampus::factory()->create();
        $ciclo              = AccreditationCycle::factory()->create([
            'carrera_sede_id'      => $careerCampus->carrera_sede_id,
            'modelo_estructura_id' => $modelo->modelo_estructura_id,
        ]);
        $this->proceso  = Process::factory()->create([
            'ciclo_acreditacion_id' => $ciclo->ciclo_acreditacion_id,
        ]);
        $this->elemento = StructureElement::factory()->create([
            'modelo_estructura_id' => $modelo->modelo_estructura_id,
            'padre_id'             => null,
        ]);
    }

    // ─── Helper ──────────────────────────────────────────────────────────────

    private function aprobarPayload(array $overrides = []): array
    {
        return array_merge([
            'proceso_id' => $this->proceso->proceso_id,
            'comentario' => 'Elemento revisado correctamente.',
        ], $overrides);
    }

    // ─── Control de acceso ───────────────────────────────────────────────────

    public function test_retorna_401_sin_token_en_aprobar(): void
    {
        $this->postJson("/api/elementos/{$this->elemento->elemento_id}/aprobar", $this->aprobarPayload())
             ->assertStatus(401);
    }

    public function test_retorna_401_sin_token_en_rechazar(): void
    {
        $this->postJson("/api/elementos/{$this->elemento->elemento_id}/rechazar", $this->aprobarPayload())
             ->assertStatus(401);
    }

    public function test_retorna_401_sin_token_en_listApprovals(): void
    {
        $this->getJson('/api/aprobaciones-elementos')->assertStatus(401);
    }

    public function test_retorna_403_a_Profesor_en_aprobar(): void
    {
        $profesor = User::factory()->create();
        $profesor->assignRole(Role::where('name', 'Profesor')->first());
        Sanctum::actingAs($profesor);

        $this->postJson("/api/elementos/{$this->elemento->elemento_id}/aprobar", $this->aprobarPayload())
             ->assertStatus(403);
    }

    public function test_retorna_403_a_Profesor_en_rechazar(): void
    {
        $profesor = User::factory()->create();
        $profesor->assignRole(Role::where('name', 'Profesor')->first());
        Sanctum::actingAs($profesor);

        $this->postJson("/api/elementos/{$this->elemento->elemento_id}/rechazar", $this->aprobarPayload())
             ->assertStatus(403);
    }

    // ─── Validación de entrada ────────────────────────────────────────────────

    public function test_aprobar_retorna_422_sin_proceso_id(): void
    {
        Sanctum::actingAs($this->superusuario);

        $this->postJson("/api/elementos/{$this->elemento->elemento_id}/aprobar", [])
             ->assertStatus(422)
             ->assertJsonValidationErrors(['proceso_id']);
    }

    public function test_rechazar_retorna_422_sin_proceso_id(): void
    {
        Sanctum::actingAs($this->superusuario);

        $this->postJson("/api/elementos/{$this->elemento->elemento_id}/rechazar", [])
             ->assertStatus(422)
             ->assertJsonValidationErrors(['proceso_id']);
    }

    public function test_aprobar_retorna_422_con_proceso_id_inexistente(): void
    {
        Sanctum::actingAs($this->superusuario);

        $this->postJson("/api/elementos/{$this->elemento->elemento_id}/aprobar", [
            'proceso_id' => 999999,
        ])->assertStatus(422)
          ->assertJsonValidationErrors(['proceso_id']);
    }

    public function test_aprobar_retorna_422_cuando_elemento_no_existe(): void
    {
        Sanctum::actingAs($this->superusuario);

        $this->postJson("/api/elementos/999999/aprobar", $this->aprobarPayload())
             ->assertStatus(422);
    }

    public function test_rechazar_retorna_422_cuando_elemento_no_existe(): void
    {
        Sanctum::actingAs($this->superusuario);

        $this->postJson("/api/elementos/999999/rechazar", $this->aprobarPayload())
             ->assertStatus(422);
    }

    public function test_aprobar_retorna_422_cuando_comentario_supera_100_caracteres(): void
    {
        Sanctum::actingAs($this->superusuario);

        $this->postJson("/api/elementos/{$this->elemento->elemento_id}/aprobar", [
            'proceso_id' => $this->proceso->proceso_id,
            'comentario' => str_repeat('A', 101),
        ])->assertStatus(422)
          ->assertJsonValidationErrors(['comentario']);
    }

    // ─── Flujo feliz: aprobar ─────────────────────────────────────────────────

    public function test_superusuario_aprueba_elemento_y_retorna_201(): void
    {
        Sanctum::actingAs($this->superusuario);

        $response = $this->postJson("/api/elementos/{$this->elemento->elemento_id}/aprobar", $this->aprobarPayload())
             ->assertStatus(201);

        $response->assertJsonPath('data.raiz.estado', 'aprobado');

        $this->assertDatabaseHas('APROBACION_ELEMENTO', [
            'elemento_id' => $this->elemento->elemento_id,
            'proceso_id'  => $this->proceso->proceso_id,
            'estado'      => 'aprobado',
        ]);
    }

    public function test_aprobar_dos_veces_retorna_422(): void
    {
        Sanctum::actingAs($this->superusuario);

        // Primera aprobación
        $this->postJson("/api/elementos/{$this->elemento->elemento_id}/aprobar", $this->aprobarPayload())
             ->assertStatus(201);

        // Segunda aprobación debe fallar
        $this->postJson("/api/elementos/{$this->elemento->elemento_id}/aprobar", $this->aprobarPayload())
             ->assertStatus(422);
    }

    // ─── Flujo feliz: rechazar ────────────────────────────────────────────────

    public function test_superusuario_rechaza_elemento_y_retorna_201(): void
    {
        Sanctum::actingAs($this->superusuario);

        $response = $this->postJson("/api/elementos/{$this->elemento->elemento_id}/rechazar", $this->aprobarPayload())
             ->assertStatus(201);

        $response->assertJsonPath('data.raiz.estado', 'rechazado');

        $this->assertDatabaseHas('APROBACION_ELEMENTO', [
            'elemento_id' => $this->elemento->elemento_id,
            'proceso_id'  => $this->proceso->proceso_id,
            'estado'      => 'rechazado',
        ]);
    }

    public function test_rechazar_dos_veces_retorna_422(): void
    {
        Sanctum::actingAs($this->superusuario);

        $this->postJson("/api/elementos/{$this->elemento->elemento_id}/rechazar", $this->aprobarPayload())
             ->assertStatus(201);

        $this->postJson("/api/elementos/{$this->elemento->elemento_id}/rechazar", $this->aprobarPayload())
             ->assertStatus(422);
    }

    // ─── Listado y detalle ────────────────────────────────────────────────────

    public function test_GET_listApprovals_retorna_200_con_data(): void
    {
        Sanctum::actingAs($this->superusuario);

        // Crear una aprobación existente
        ElementApproval::factory()->create([
            'elemento_id' => $this->elemento->elemento_id,
            'proceso_id'  => $this->proceso->proceso_id,
            'usuario_id'  => $this->superusuario->usuario_id,
            'estado'      => 'aprobado',
        ]);

        $response = $this->getJson('/api/aprobaciones-elementos')->assertStatus(200);

        $this->assertTrue($response->json('success'));
        $this->assertIsArray($response->json('data'));
    }

    public function test_GET_showApproval_retorna_200_para_aprobacion_existente(): void
    {
        Sanctum::actingAs($this->superusuario);

        $aprobacion = ElementApproval::factory()->create([
            'elemento_id' => $this->elemento->elemento_id,
            'proceso_id'  => $this->proceso->proceso_id,
            'usuario_id'  => $this->superusuario->usuario_id,
            'estado'      => 'aprobado',
        ]);

        $this->getJson("/api/aprobaciones-elementos/{$aprobacion->aprobacion_elemento_id}")
             ->assertStatus(200)
             ->assertJsonPath('success', true);
    }

    public function test_GET_showApproval_retorna_404_si_no_existe(): void
    {
        Sanctum::actingAs($this->superusuario);

        $this->getJson('/api/aprobaciones-elementos/999999')
             ->assertStatus(404);
    }

    // ─── Cascada sobre hijos ─────────────────────────────────────────────────

    public function test_aprobar_elemento_padre_aprueba_hijos_en_cascada(): void
    {
        Sanctum::actingAs($this->superusuario);

        $modelo = $this->elemento->modelo_estructura_id;

        // Crear dos hijos activos del elemento raíz
        $hijo1 = StructureElement::factory()->create([
            'modelo_estructura_id' => $modelo,
            'padre_id'             => $this->elemento->elemento_id,
            'activo'               => true,
        ]);
        $hijo2 = StructureElement::factory()->create([
            'modelo_estructura_id' => $modelo,
            'padre_id'             => $this->elemento->elemento_id,
            'activo'               => true,
        ]);

        $response = $this->postJson("/api/elementos/{$this->elemento->elemento_id}/aprobar", $this->aprobarPayload())
             ->assertStatus(201);

        // El raíz está aprobado
        $this->assertDatabaseHas('APROBACION_ELEMENTO', [
            'elemento_id' => $this->elemento->elemento_id,
            'estado'      => 'aprobado',
        ]);

        // Los hijos también se aprobaron en cascada
        $this->assertDatabaseHas('APROBACION_ELEMENTO', [
            'elemento_id' => $hijo1->elemento_id,
            'estado'      => 'aprobado',
        ]);
        $this->assertDatabaseHas('APROBACION_ELEMENTO', [
            'elemento_id' => $hijo2->elemento_id,
            'estado'      => 'aprobado',
        ]);

        // La respuesta indica la cascada
        $this->assertCount(2, $response->json('data.cascada'));
    }
}
