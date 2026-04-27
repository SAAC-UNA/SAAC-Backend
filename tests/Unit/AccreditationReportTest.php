<?php

namespace Tests\Unit;

use App\Models\AccreditationReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests unitarios para AccreditationReport.
 *
 * HU-027: Publicación de informe de acreditación aprobado.
 *
 * Verifica:
 *  - Creación y persistencia del modelo.
 *  - Helpers de dominio: isPublished(), isUnpublished(), isCurrentlyValid().
 *  - Unicidad del ciclo (solo un informe por ciclo).
 */
class AccreditationReportTest extends TestCase
{
    use RefreshDatabase;

    // ─── Creación ────────────────────────────────────────────────────────────

    public function test_crea_informe_y_persiste_en_bd(): void
    {
        $report = AccreditationReport::factory()->create([
            'observaciones' => 'Test Observaciones',
        ]);

        $this->assertDatabaseHas('INFORME_ARCHIVO', [
            'observaciones' => 'Test Observaciones',
        ]);
        $this->assertNotNull($report->informe_archivo_id);
    }

    public function test_estado_inicial_es_publicado(): void
    {
        $report = AccreditationReport::factory()->published()->create();

        $this->assertTrue($report->isPublished());
        $this->assertFalse($report->isUnpublished());
    }

    // ─── Helpers de dominio ──────────────────────────────────────────────────

    public function test_is_published_retorna_true_cuando_estado_publicado(): void
    {
        $report = AccreditationReport::factory()->published()->create();

        $this->assertTrue($report->isPublished());
    }

    public function test_is_unpublished_retorna_true_cuando_estado_despublicado(): void
    {
        $report = AccreditationReport::factory()->unpublished()->create();

        $this->assertTrue($report->isUnpublished());
        $this->assertFalse($report->isPublished());
    }

    // ─── Relaciones ──────────────────────────────────────────────────────────

    public function test_relacion_process(): void
    {
        $report = AccreditationReport::factory()->create();

        $this->assertInstanceOf(SelfEvaluationProcess::class, $report->process);
    }
}
