<?php

namespace Tests\Unit;

use App\Models\AccreditationCycle;
use App\Models\AccreditationReport;
use App\Models\File;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests unitarios para AccreditationReport.
 *
 * HU-028: Publicación de informe de acreditación aprobado.
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
            'numero_resolucion' => 'RES-2026-0001',
        ]);

        $this->assertDatabaseHas('INFORME_ACREDITACION', [
            'numero_resolucion' => 'RES-2026-0001',
        ]);
        $this->assertNotNull($report->informe_acreditacion_id);
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

    public function test_is_currently_valid_retorna_true_dentro_de_vigencia(): void
    {
        $report = AccreditationReport::factory()->published()->create([
            'vigencia_desde' => now()->subYear()->format('Y-m-d'),
            'vigencia_hasta' => now()->addYear()->format('Y-m-d'),
        ]);

        $this->assertTrue($report->isCurrentlyValid());
    }

    public function test_is_currently_valid_retorna_false_fuera_de_vigencia(): void
    {
        $report = AccreditationReport::factory()->published()->create([
            'vigencia_desde' => now()->subYears(3)->format('Y-m-d'),
            'vigencia_hasta' => now()->subYear()->format('Y-m-d'),
        ]);

        $this->assertFalse($report->isCurrentlyValid());
    }

    // ─── Relaciones ──────────────────────────────────────────────────────────

    public function test_relacion_accreditation_cycle(): void
    {
        $report = AccreditationReport::factory()->create();

        $this->assertInstanceOf(AccreditationCycle::class, $report->accreditationCycle);
    }

    public function test_relacion_file(): void
    {
        $report = AccreditationReport::factory()->create();

        $this->assertInstanceOf(File::class, $report->file);
    }

    public function test_relacion_published_by(): void
    {
        $report = AccreditationReport::factory()->create();

        $this->assertInstanceOf(User::class, $report->publishedBy);
    }

    // ─── Unicidad de ciclo ───────────────────────────────────────────────────

    public function test_un_ciclo_solo_puede_tener_un_informe(): void
    {
        $cycle  = AccreditationCycle::factory()->create();
        AccreditationReport::factory()->create(['ciclo_acreditacion_id' => $cycle->ciclo_acreditacion_id]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        AccreditationReport::factory()->create(['ciclo_acreditacion_id' => $cycle->ciclo_acreditacion_id]);
    }
}
