<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use App\Models\Evidence;
use App\Models\Criterion;
use App\Models\Component;
use App\Models\Dimension;
use App\Models\EvidenceState;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Storage;

class EvidenceExportFeatureTest extends TestCase
{
    use RefreshDatabase;

    private string $excelEndpoint = '/api/estructura/evidencias/export/excel';
    private string $pdfEndpoint = '/api/estructura/evidencias/export/pdf';
    private User $superUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear roles con guard 'api' para Sanctum
        Role::create(['name' => 'SuperUsuario', 'guard_name' => 'api']);

        // Crear usuario SuperUsuario
        $this->superUser = User::factory()->create();
        $this->superUser->assignRole('SuperUsuario');

        // Configurar storage para tests
        Storage::fake('local');
    }

    #[Test]
    public function export_excel_requiere_autenticacion()
    {
        $this->getJson($this->excelEndpoint)
            ->assertUnauthorized();
    }

    #[Test]
    public function export_pdf_requiere_autenticacion()
    {
        $this->getJson($this->pdfEndpoint)
            ->assertUnauthorized();
    }

    #[Test]
    public function export_excel_descarga_archivo_correctamente()
    {
        Sanctum::actingAs($this->superUser);

        // Crear datos de prueba
        $dimension = Dimension::factory()->create();
        $component = Component::factory()->create(['dimension_id' => $dimension->getKey()]);
        $criterion = Criterion::factory()->create(['componente_id' => $component->getKey()]);
        $state = EvidenceState::factory()->create();

        Evidence::factory()->count(3)->create([
            'criterio_id' => $criterion->getKey(),
            'estado_evidencia_id' => $state->getKey(),
        ]);

        $response = $this->getJson($this->excelEndpoint);

        $response->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->assertDownload();
    }

    #[Test]
    public function export_pdf_descarga_archivo_correctamente()
    {
        Sanctum::actingAs($this->superUser);

        // Crear datos de prueba
        $dimension = Dimension::factory()->create();
        $component = Component::factory()->create(['dimension_id' => $dimension->getKey()]);
        $criterion = Criterion::factory()->create(['componente_id' => $component->getKey()]);
        $state = EvidenceState::factory()->create();

        Evidence::factory()->count(3)->create([
            'criterio_id' => $criterion->getKey(),
            'estado_evidencia_id' => $state->getKey(),
        ]);

        $response = $this->getJson($this->pdfEndpoint);

        $response->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertDownload();
    }

    #[Test]
    public function export_excel_con_filtro_por_criterio()
    {
        Sanctum::actingAs($this->superUser);

        $dimension = Dimension::factory()->create();
        $component = Component::factory()->create(['dimension_id' => $dimension->getKey()]);
        $criterion1 = Criterion::factory()->create(['componente_id' => $component->getKey()]);
        $criterion2 = Criterion::factory()->create(['componente_id' => $component->getKey()]);
        $state = EvidenceState::factory()->create();

        // 2 evidencias del criterio 1
        Evidence::factory()->count(2)->create([
            'criterio_id' => $criterion1->getKey(),
            'estado_evidencia_id' => $state->getKey(),
        ]);

        // 3 evidencias del criterio 2
        Evidence::factory()->count(3)->create([
            'criterio_id' => $criterion2->getKey(),
            'estado_evidencia_id' => $state->getKey(),
        ]);

        $response = $this->getJson($this->excelEndpoint . '?criterio_id=' . $criterion1->getKey());

        $response->assertOk()
            ->assertDownload();
    }

    #[Test]
    public function export_pdf_con_filtro_por_estado()
    {
        Sanctum::actingAs($this->superUser);

        $dimension = Dimension::factory()->create();
        $component = Component::factory()->create(['dimension_id' => $dimension->getKey()]);
        $criterion = Criterion::factory()->create(['componente_id' => $component->getKey()]);
        $state1 = EvidenceState::factory()->create(['nombre' => 'Pendiente']);
        $state2 = EvidenceState::factory()->create(['nombre' => 'Completado']);

        Evidence::factory()->count(2)->create([
            'criterio_id' => $criterion->getKey(),
            'estado_evidencia_id' => $state1->getKey(),
        ]);

        Evidence::factory()->count(3)->create([
            'criterio_id' => $criterion->getKey(),
            'estado_evidencia_id' => $state2->getKey(),
        ]);

        $response = $this->getJson($this->pdfEndpoint . '?estado_evidencia_id=' . $state1->getKey());

        $response->assertOk()
            ->assertDownload();
    }

    #[Test]
    public function export_excel_con_multiples_filtros()
    {
        Sanctum::actingAs($this->superUser);

        $dimension = Dimension::factory()->create();
        $component = Component::factory()->create(['dimension_id' => $dimension->getKey()]);
        $criterion = Criterion::factory()->create(['componente_id' => $component->getKey()]);
        $state = EvidenceState::factory()->create();

        Evidence::factory()->count(5)->create([
            'criterio_id' => $criterion->getKey(),
            'estado_evidencia_id' => $state->getKey(),
        ]);

        $response = $this->getJson(
            $this->excelEndpoint . 
            '?criterio_id=' . $criterion->getKey() . 
            '&estado_evidencia_id=' . $state->getKey() .
            '&sort_by=nomenclatura&sort_order=asc'
        );

        $response->assertOk()
            ->assertDownload();
    }

    #[Test]
    public function export_excel_sin_evidencias_descarga_archivo_vacio()
    {
        Sanctum::actingAs($this->superUser);

        // No crear evidencias

        $response = $this->getJson($this->excelEndpoint);

        $response->assertOk()
            ->assertDownload();
    }

    #[Test]
    public function export_pdf_sin_evidencias_descarga_archivo_vacio()
    {
        Sanctum::actingAs($this->superUser);

        // No crear evidencias

        $response = $this->getJson($this->pdfEndpoint);

        $response->assertOk()
            ->assertDownload();
    }

    #[Test]
    public function export_excel_valida_parametros_incorrectos()
    {
        Sanctum::actingAs($this->superUser);

        $response = $this->getJson($this->excelEndpoint . '?criterio_id=abc&per_page=abc');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['criterio_id', 'per_page']);
    }

    #[Test]
    public function export_pdf_valida_parametros_incorrectos()
    {
        Sanctum::actingAs($this->superUser);

        $response = $this->getJson($this->pdfEndpoint . '?estado_evidencia_id=xyz&sort_order=invalid');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['estado_evidencia_id', 'sort_order']);
    }
}
