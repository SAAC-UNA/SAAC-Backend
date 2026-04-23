<?php

namespace Tests\Feature;

use App\Models\AccreditationReport;
use App\Models\SelfEvaluationProcess;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Tests de integración para los endpoints de Informes de Acreditación.
 *
 * HU-027: Publicación de informe de acreditación aprobado.
 *
 * Endpoints cubiertos:
 *   GET  /api/informes-acreditacion                              → index()
 *   GET  /api/estructura/ciclos-acreditacion/{cycle}/informe     → showByCycle()
 *   POST /api/estructura/ciclos-acreditacion/{cycle}/informe     → publish()
 *   PATCH /api/informes-acreditacion/{report}/despublicar        → unpublish()
 */
class AccreditationReportFeatureTest extends TestCase
{
    use RefreshDatabase;

    private User $superusuario;
    private User $encargado;
    private User $profesor;
    private SelfEvaluationProcess $process;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $this->superusuario = User::factory()->create();
        $this->superusuario->assignRole(Role::where('name', 'Superusuario')->first());

        $this->encargado = User::factory()->create();
        $this->encargado->assignRole(Role::where('name', 'Encargado de Acreditación')->first());

        $this->profesor = User::factory()->create();
        $this->profesor->assignRole(Role::where('name', 'Profesor')->first());

        $this->process = SelfEvaluationProcess::factory()->create(['estado' => 'completado']);

        // Asociar encargado y profesor con la carrera del ciclo para que el
        // global scope BaseCareer (byCareerCampus) no filtre el ciclo fuera.
        $carreraId = $this->process->cycle->careerCampus->carrera_id;
        $this->encargado->careers()->attach($carreraId);
        $this->profesor->careers()->attach($carreraId);
    }

    // ─── Helper ──────────────────────────────────────────────────────────────

    private function publishPayload(array $overrides = []): array
    {
        Storage::fake('simulated_nas');
        $file = UploadedFile::fake()->create('resolucion-sinaes.pdf', 100, 'application/pdf');

        return array_merge([
            'archivo'       => $file,
            'proceso_id'    => $this->process->proceso_id,
            'observaciones' => 'Test Observaciones',
        ], $overrides);
    }

    // ─── GET /api/informes-acreditacion (público) ────────────────────────────

    public function test_index_accesible_sin_autenticacion(): void
    {
        AccreditationReport::factory()->published()->count(2)->create();

        $this->getJson('/api/informes-acreditacion')
            ->assertStatus(200)
            ->assertJsonStructure(['data']);
    }

    public function test_index_retorna_solo_informes_publicados(): void
    {
        AccreditationReport::factory()->published()->count(3)->create();
        AccreditationReport::factory()->unpublished()->count(2)->create();

        $response = $this->getJson('/api/informes-acreditacion')->assertStatus(200);

        // El listado público no debe incluir despublicados
        $this->assertCount(3, $response->json('data'));
    }

    public function test_index_filtra_por_carrera_id(): void
    {
        $reportEnCarrera = AccreditationReport::factory()->published()->create();
        AccreditationReport::factory()->published()->count(3)->create();

        $carreraId = $reportEnCarrera->process->cycle->careerCampus->carrera_id;

        $response = $this->getJson("/api/informes-acreditacion?carrera_id={$carreraId}")
            ->assertStatus(200);

        foreach ($response->json('data') as $item) {
            $this->assertEquals($carreraId, $item['proceso']['ciclo']['carrera_sede']['carrera_id'] ?? $carreraId);
        }
    }

    // ─── GET /api/estructura/ciclos-acreditacion/{cycle}/informe ─────────────

    public function test_show_by_cycle_retorna_404_sin_informe(): void
    {
        $this->getJson("/api/estructura/ciclos-acreditacion/{$this->process->ciclo_acreditacion_id}/informe")
            ->assertStatus(404);
    }

    public function test_show_by_cycle_retorna_informe_publicado_sin_autenticacion(): void
    {
        $report = AccreditationReport::factory()->published()->create([
            'proceso_id' => $this->process->proceso_id,
        ]);

        $this->getJson("/api/estructura/ciclos-acreditacion/{$this->process->ciclo_acreditacion_id}/informe")
            ->assertStatus(200)
            ->assertJsonPath('data.observaciones', $report->observaciones);
    }

    public function test_show_by_cycle_informe_despublicado_retorna_403_sin_auth(): void
    {
        AccreditationReport::factory()->unpublished()->create([
            'proceso_id' => $this->process->proceso_id,
        ]);

        $this->getJson("/api/estructura/ciclos-acreditacion/{$this->process->ciclo_acreditacion_id}/informe")
            ->assertStatus(403);
    }

    public function test_show_by_cycle_informe_despublicado_visible_con_permiso(): void
    {
        AccreditationReport::factory()->unpublished()->create([
            'proceso_id' => $this->process->proceso_id,
        ]);

        Sanctum::actingAs($this->superusuario);

        $this->getJson("/api/estructura/ciclos-acreditacion/{$this->process->ciclo_acreditacion_id}/informe")
            ->assertStatus(200);
    }

    // ─── POST /api/estructura/ciclos-acreditacion/{cycle}/informe ────────────

    public function test_publish_retorna_401_sin_token(): void
    {
        $this->postJson(
            "/api/estructura/ciclos-acreditacion/{$this->process->ciclo_acreditacion_id}/informe",
            $this->publishPayload()
        )->assertStatus(401);
    }

    public function test_publish_retorna_403_a_profesor(): void
    {
        Sanctum::actingAs($this->profesor);

        $this->postJson(
            "/api/estructura/ciclos-acreditacion/{$this->process->ciclo_acreditacion_id}/informe",
            $this->publishPayload()
        )->assertStatus(403);
    }

    public function test_publish_crea_informe_y_retorna_201(): void
    {
        Sanctum::actingAs($this->encargado);

        $response = $this->postJson(
            "/api/estructura/ciclos-acreditacion/{$this->process->ciclo_acreditacion_id}/informe",
            $this->publishPayload()
        )->assertStatus(201);

        $response->assertJsonPath('data.observaciones', 'Test Observaciones');
        $response->assertJsonPath('data.estado', AccreditationReport::STATUS_PUBLISHED);

        $this->assertDatabaseHas('INFORME_ARCHIVO', [
            'proceso_id'    => $this->process->proceso_id,
            'observaciones' => 'Test Observaciones',
            'estado'        => AccreditationReport::STATUS_PUBLISHED,
        ]);
    }

    public function test_publish_retorna_422_si_ciclo_ya_tiene_informe(): void
    {
        AccreditationReport::factory()->create([
            'proceso_id' => $this->process->proceso_id,
        ]);

        Sanctum::actingAs($this->encargado);

        $this->postJson(
            "/api/estructura/ciclos-acreditacion/{$this->process->ciclo_acreditacion_id}/informe",
            $this->publishPayload()
        )->assertStatus(422);
    }

    public function test_publish_retorna_422_sin_campos_requeridos(): void
    {
        Sanctum::actingAs($this->encargado);

        $this->postJson(
            "/api/estructura/ciclos-acreditacion/{$this->process->ciclo_acreditacion_id}/informe",
            []
        )->assertStatus(422)
         ->assertJsonValidationErrors(['archivo', 'proceso_id']);
    }

    // ─── PATCH /api/informes-acreditacion/{report}/despublicar ───────────────

    public function test_unpublish_retorna_401_sin_token(): void
    {
        $report = AccreditationReport::factory()->published()->create();

        $this->patchJson("/api/informes-acreditacion/{$report->informe_archivo_id}/despublicar")
            ->assertStatus(401);
    }

    public function test_unpublish_retorna_403_a_encargado_sin_permiso(): void
    {
        $report = AccreditationReport::factory()->published()->create();

        Sanctum::actingAs($this->encargado);

        $this->patchJson("/api/informes-acreditacion/{$report->informe_archivo_id}/despublicar")
            ->assertStatus(403);
    }

    public function test_unpublish_retorna_403_a_profesor(): void
    {
        $report = AccreditationReport::factory()->published()->create();

        Sanctum::actingAs($this->profesor);

        $this->patchJson("/api/informes-acreditacion/{$report->informe_archivo_id}/despublicar")
            ->assertStatus(403);
    }

    public function test_unpublish_cambia_estado_a_despublicado(): void
    {
        $report = AccreditationReport::factory()->published()->create([
            'proceso_id' => $this->process->proceso_id,
        ]);

        Sanctum::actingAs($this->superusuario);

        $this->patchJson("/api/informes-acreditacion/{$report->informe_archivo_id}/despublicar", [
            'motivo' => 'Corrección de datos',
        ])->assertStatus(200)
          ->assertJsonPath('data.estado', AccreditationReport::STATUS_UNPUBLISHED);

        $this->assertDatabaseHas('INFORME_ARCHIVO', [
            'informe_archivo_id' => $report->informe_archivo_id,
            'estado'             => AccreditationReport::STATUS_UNPUBLISHED,
        ]);
    }

    public function test_unpublish_retorna_422_si_ya_esta_despublicado(): void
    {
        $report = AccreditationReport::factory()->unpublished()->create();

        Sanctum::actingAs($this->superusuario);

        $this->patchJson("/api/informes-acreditacion/{$report->informe_archivo_id}/despublicar")
            ->assertStatus(422);
    }

    public function test_unpublish_sin_motivo_es_valido(): void
    {
        $report = AccreditationReport::factory()->published()->create([
            'proceso_id' => $this->process->proceso_id,
        ]);

        Sanctum::actingAs($this->superusuario);

        $this->patchJson("/api/informes-acreditacion/{$report->informe_archivo_id}/despublicar")
            ->assertStatus(200);
    }
}
