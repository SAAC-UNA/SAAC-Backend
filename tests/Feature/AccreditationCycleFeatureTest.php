<?php

namespace Tests\Feature;

use App\Models\AccreditationCycle;
use App\Models\CareerCampus;
use App\Models\StructureModel;
use App\Models\User;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AccreditationCycleFeatureTest extends TestCase
{
    use RefreshDatabase;

    private string $baseEndpoint = '/api/estructura/ciclos-acreditacion';
    private User $superusuario;
    private CareerCampus $careerCampus;
    private StructureModel $modelo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        // Usuario con todos los permisos de ciclos
        $this->superusuario = User::factory()->create();
        $this->superusuario->assignRole(Role::where('name', 'Superusuario')->first());

        // Datos base reutilizables
        $this->careerCampus = CareerCampus::factory()->create();
        $this->modelo       = StructureModel::factory()->create();
    }

    // ─── Helper ──────────────────────────────────────────────────────────────

    private function cicloPayload(array $overrides = []): array
    {
        return array_merge([
            'carrera_sede_id'      => $this->careerCampus->carrera_sede_id,
            'modelo_estructura_id' => $this->modelo->modelo_estructura_id,
            'nombre'               => 'Ciclo Test ' . uniqid(),
            'estado'               => 'activo',
        ], $overrides);
    }

    // ─── AC-5: Autenticación ─────────────────────────────────────────────────

    public function test_retorna_401_sin_token_en_get_index(): void
    {
        $this->getJson($this->baseEndpoint)->assertStatus(401);
    }

    public function test_retorna_403_a_usuario_sin_permiso_ciclos_view(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::where('name', 'Profesor')->first());
        Sanctum::actingAs($user);

        $this->getJson($this->baseEndpoint)->assertStatus(403);
    }

    // ─── AC-1 y AC-3: Crear y listar ciclos ──────────────────────────────────

    public function test_post_crea_ciclo_valido_y_retorna_201_AC1(): void
    {
        Sanctum::actingAs($this->superusuario);

        $response = $this->postJson($this->baseEndpoint, [
            'carrera_sede_id'      => $this->careerCampus->carrera_sede_id,
            'modelo_estructura_id' => $this->modelo->modelo_estructura_id,
            'nombre'               => 'Ciclo 2026-2030',
            'fecha_inicio'         => now()->toDateString(),
            'fecha_fin'            => now()->addYears(4)->toDateString(),
        ])->assertStatus(201);

        $response->assertJsonPath('data.nombre', 'Ciclo 2026-2030');
        $response->assertJsonPath('data.estado', 'activo');

        $this->assertDatabaseHas('CICLO_ACREDITACION', [
            'nombre'          => 'Ciclo 2026-2030',
            'carrera_sede_id' => $this->careerCampus->carrera_sede_id,
        ]);
    }

    public function test_get_index_retorna_ciclos_paginados_con_estructura_meta_AC3(): void
    {
        Sanctum::actingAs($this->superusuario);

        AccreditationCycle::factory()->count(3)->create([
            'carrera_sede_id'      => $this->careerCampus->carrera_sede_id,
            'modelo_estructura_id' => $this->modelo->modelo_estructura_id,
        ]);

        $response = $this->getJson($this->baseEndpoint)->assertStatus(200);

        $response->assertJsonStructure(['data', 'meta']);
        $this->assertGreaterThanOrEqual(3, count($response->json('data')));
    }

    public function test_get_show_retorna_un_ciclo_existente_AC1(): void
    {
        Sanctum::actingAs($this->superusuario);

        $ciclo = AccreditationCycle::factory()->create([
            'carrera_sede_id'      => $this->careerCampus->carrera_sede_id,
            'modelo_estructura_id' => $this->modelo->modelo_estructura_id,
            'nombre'               => 'Ciclo Show Test',
        ]);

        $this->getJson("{$this->baseEndpoint}/{$ciclo->ciclo_acreditacion_id}")
             ->assertStatus(200)
             ->assertJsonPath('data.nombre', 'Ciclo Show Test');
    }

    public function test_get_show_retorna_404_si_el_ciclo_no_existe(): void
    {
        Sanctum::actingAs($this->superusuario);

        $this->getJson("{$this->baseEndpoint}/999999")->assertStatus(404);
    }

    // ─── AC-2: Validaciones de entrada ───────────────────────────────────────

    public function test_post_permite_crear_ciclo_sin_nombre_y_lo_autogenera_AC2(): void
    {
        Sanctum::actingAs($this->superusuario);

        $response = $this->postJson($this->baseEndpoint, [
            'carrera_sede_id'      => $this->careerCampus->carrera_sede_id,
            'modelo_estructura_id' => $this->modelo->modelo_estructura_id,
            'fecha_inicio'         => now()->toDateString(),
            'fecha_fin'            => now()->addYears(4)->toDateString(),
        ])->assertStatus(201);

        $this->assertStringStartsWith('Ciclo ', $response->json('data.nombre'));
    }

    public function test_post_retorna_422_sin_carrera_sede_id_AC2(): void
    {
        Sanctum::actingAs($this->superusuario);

        $this->postJson($this->baseEndpoint, [
            'modelo_estructura_id' => $this->modelo->modelo_estructura_id,
            'nombre'               => 'Ciclo sin sede',
        ])->assertStatus(422)->assertJsonValidationErrors(['carrera_sede_id']);
    }

    public function test_post_retorna_422_sin_modelo_estructura_id_AC2(): void
    {
        Sanctum::actingAs($this->superusuario);

        $this->postJson($this->baseEndpoint, [
            'carrera_sede_id' => $this->careerCampus->carrera_sede_id,
            'nombre'          => 'Ciclo sin modelo',
        ])->assertStatus(422)->assertJsonValidationErrors(['modelo_estructura_id']);
    }

    public function test_post_retorna_422_con_estado_invalido_AC2(): void
    {
        Sanctum::actingAs($this->superusuario);

        $this->postJson($this->baseEndpoint, [
            'carrera_sede_id'      => $this->careerCampus->carrera_sede_id,
            'modelo_estructura_id' => $this->modelo->modelo_estructura_id,
            'nombre'               => 'Ciclo estado invalido',
            'estado'               => 'eliminado',
        ])->assertStatus(422)->assertJsonValidationErrors(['estado']);
    }

    // ─── AC-6: Máximo un ciclo activo por carrera+sede ───────────────────────

    public function test_post_retorna_422_cuando_ya_existe_ciclo_activo_en_misma_sede_AC6(): void
    {
        Sanctum::actingAs($this->superusuario);

        AccreditationCycle::factory()->create([
            'carrera_sede_id'      => $this->careerCampus->carrera_sede_id,
            'modelo_estructura_id' => $this->modelo->modelo_estructura_id,
            'estado'               => 'activo',
        ]);

        $this->postJson($this->baseEndpoint, [
            'carrera_sede_id'      => $this->careerCampus->carrera_sede_id,
            'modelo_estructura_id' => $this->modelo->modelo_estructura_id,
            'nombre'               => 'Segundo ciclo activo',
            'estado'               => 'activo',
        ])->assertStatus(422)
          ->assertJsonValidationErrors(['carrera_sede_id']);
    }

    public function test_post_permite_ciclo_activo_en_sede_diferente_AC6(): void
    {
        Sanctum::actingAs($this->superusuario);

        AccreditationCycle::factory()->create([
            'carrera_sede_id'      => $this->careerCampus->carrera_sede_id,
            'modelo_estructura_id' => $this->modelo->modelo_estructura_id,
            'estado'               => 'activo',
        ]);

        $otraCareerCampus = CareerCampus::factory()->create();

        $this->postJson($this->baseEndpoint, [
            'carrera_sede_id'      => $otraCareerCampus->carrera_sede_id,
            'modelo_estructura_id' => $this->modelo->modelo_estructura_id,
            'nombre'               => 'Ciclo otra sede',
            'estado'               => 'activo',
            'fecha_inicio'         => now()->toDateString(),
            'fecha_fin'            => now()->addYears(4)->toDateString(),
        ])->assertStatus(201);
    }

    // ─── AC-4: Edición bloqueada si no está activo ───────────────────────────

    public function test_patch_actualiza_ciclo_activo_exitosamente_AC4(): void
    {
        Sanctum::actingAs($this->superusuario);

        $ciclo = AccreditationCycle::factory()->create([
            'carrera_sede_id'      => $this->careerCampus->carrera_sede_id,
            'modelo_estructura_id' => $this->modelo->modelo_estructura_id,
            'nombre'               => 'Nombre Original',
            'estado'               => 'activo',
        ]);

        $this->patchJson("{$this->baseEndpoint}/{$ciclo->ciclo_acreditacion_id}", [
            'nombre' => 'Nombre Actualizado',
        ])->assertStatus(200)
          ->assertJsonPath('data.nombre', 'Nombre Original');
    }

    public function test_patch_permite_editar_ciclo_inactivo_AC4(): void
    {
        Sanctum::actingAs($this->superusuario);

        $ciclo = AccreditationCycle::factory()->create([
            'carrera_sede_id'      => $this->careerCampus->carrera_sede_id,
            'modelo_estructura_id' => $this->modelo->modelo_estructura_id,
            'estado'               => 'inactivo',
        ]);

        $this->patchJson("{$this->baseEndpoint}/{$ciclo->ciclo_acreditacion_id}", [
            'nombre' => 'Intento de edicion',
        ])->assertStatus(200);
    }

    public function test_patch_retorna_403_al_editar_ciclo_completado_AC4(): void
    {
        Sanctum::actingAs($this->superusuario);

        $ciclo = AccreditationCycle::factory()->create([
            'carrera_sede_id'      => $this->careerCampus->carrera_sede_id,
            'modelo_estructura_id' => $this->modelo->modelo_estructura_id,
            'estado'               => 'completado',
        ]);

        $this->patchJson("{$this->baseEndpoint}/{$ciclo->ciclo_acreditacion_id}", [
            'nombre' => 'Intento de edicion',
        ])->assertStatus(403);
    }

    public function test_patch_sin_campos_retorna_422_AC2(): void
    {
        Sanctum::actingAs($this->superusuario);

        $ciclo = AccreditationCycle::factory()->create([
            'carrera_sede_id'      => $this->careerCampus->carrera_sede_id,
            'modelo_estructura_id' => $this->modelo->modelo_estructura_id,
            'estado'               => 'activo',
        ]);

        $this->patchJson("{$this->baseEndpoint}/{$ciclo->ciclo_acreditacion_id}", [])
             ->assertStatus(422);
    }

    // ─── AC-5: DELETE requiere permiso ciclos.delete ─────────────────────────

    public function test_delete_retorna_403_a_usuario_sin_permiso_ciclos_delete_AC5(): void
    {
        $profesor = User::factory()->create();
        $profesor->assignRole(Role::where('name', 'Profesor')->first());
        Sanctum::actingAs($profesor);

        $ciclo = AccreditationCycle::factory()->create([
            'carrera_sede_id'      => $this->careerCampus->carrera_sede_id,
            'modelo_estructura_id' => $this->modelo->modelo_estructura_id,
        ]);

        $this->deleteJson("{$this->baseEndpoint}/{$ciclo->ciclo_acreditacion_id}")
             ->assertStatus(403);
    }

    // ─── AC-R: Reactivación de ciclos (solo Superusuario) ────────────────────

    public function test_patch_reactivar_cambia_ciclo_inactivo_a_activo_ACR(): void
    {
        Sanctum::actingAs($this->superusuario);

        $ciclo = AccreditationCycle::factory()->create([
            'carrera_sede_id'      => $this->careerCampus->carrera_sede_id,
            'modelo_estructura_id' => $this->modelo->modelo_estructura_id,
            'estado'               => 'inactivo',
        ]);

        $this->patchJson("{$this->baseEndpoint}/{$ciclo->ciclo_acreditacion_id}/reactivar")
             ->assertStatus(200)
             ->assertJsonPath('data.estado', 'activo');

        $this->assertDatabaseHas('CICLO_ACREDITACION', [
            'ciclo_acreditacion_id' => $ciclo->ciclo_acreditacion_id,
            'estado'                => 'activo',
        ]);
    }

    public function test_patch_reactivar_retorna_422_si_ciclo_ya_esta_activo_ACR(): void
    {
        Sanctum::actingAs($this->superusuario);

        $ciclo = AccreditationCycle::factory()->create([
            'carrera_sede_id'      => $this->careerCampus->carrera_sede_id,
            'modelo_estructura_id' => $this->modelo->modelo_estructura_id,
            'estado'               => 'activo',
        ]);

        $this->patchJson("{$this->baseEndpoint}/{$ciclo->ciclo_acreditacion_id}/reactivar")
             ->assertStatus(422);
    }

    public function test_patch_reactivar_retorna_403_para_usuario_Administrador_ACR(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::where('name', 'Administrador')->first());
        Sanctum::actingAs($admin);

        $ciclo = AccreditationCycle::factory()->create([
            'carrera_sede_id'      => $this->careerCampus->carrera_sede_id,
            'modelo_estructura_id' => $this->modelo->modelo_estructura_id,
            'estado'               => 'inactivo',
        ]);

        $this->patchJson("{$this->baseEndpoint}/{$ciclo->ciclo_acreditacion_id}/reactivar")
             ->assertStatus(403);
    }

    public function test_patch_reactivar_retorna_422_cuando_ya_hay_otro_ciclo_activo_en_misma_sede_ACR_AC6(): void
    {
        Sanctum::actingAs($this->superusuario);

        AccreditationCycle::factory()->create([
            'carrera_sede_id'      => $this->careerCampus->carrera_sede_id,
            'modelo_estructura_id' => $this->modelo->modelo_estructura_id,
            'estado'               => 'activo',
        ]);

        $cicloInactivo = AccreditationCycle::factory()->create([
            'carrera_sede_id'      => $this->careerCampus->carrera_sede_id,
            'modelo_estructura_id' => $this->modelo->modelo_estructura_id,
            'estado'               => 'inactivo',
        ]);

        $this->patchJson("{$this->baseEndpoint}/{$cicloInactivo->ciclo_acreditacion_id}/reactivar")
             ->assertStatus(422);
    }
}

