<?php

namespace Tests\Unit;

use App\Models\Comment;
use App\Models\Component;
use App\Models\Criterion;
use App\Models\Dimension;
use App\Models\Evidence;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EvidenceTest extends TestCase
{
    use RefreshDatabase;

    // ─── Helper ──────────────────────────────────────────────────────────────

    private function makeCriterion(): Criterion
    {
        $dimension = Dimension::factory()->create();
        $component = Component::factory()->create(['dimension_id' => $dimension->dimension_id]);
        return Criterion::factory()->create(['componente_id' => $component->componente_id]);
    }

    // ─── CRUD básico ─────────────────────────────────────────────────────────

    public function test_creates_evidence(): void
    {
        $criterion = $this->makeCriterion();

        Evidence::factory()->create([
            'criterio_id' => $criterion->criterio_id,
            'descripcion' => 'Evidencia 1',
        ]);

        $this->assertDatabaseHas('EVIDENCIA', [
            'descripcion' => 'Evidencia 1',
            'criterio_id' => $criterion->criterio_id,
        ]);
    }

    public function test_requires_descripcion_field(): void
    {
        $this->expectException(QueryException::class);

        $criterion = $this->makeCriterion();

        Evidence::factory()->create([
            'criterio_id' => $criterion->criterio_id,
            'descripcion' => null,
        ]);
    }

    public function test_updates_evidence(): void
    {
        $criterion = $this->makeCriterion();

        $evidence = Evidence::factory()->create([
            'criterio_id' => $criterion->criterio_id,
            'descripcion' => 'Original',
        ]);
        $evidence->update(['descripcion' => 'Actualizado']);

        $this->assertDatabaseHas('EVIDENCIA', ['descripcion' => 'Actualizado']);
    }

    public function test_deletes_evidence(): void
    {
        $criterion = $this->makeCriterion();

        $evidence = Evidence::factory()->create([
            'criterio_id' => $criterion->criterio_id,
        ]);
        $evidence->delete();

        $this->assertDatabaseMissing('EVIDENCIA', ['evidencia_id' => $evidence->evidencia_id]);
    }

    public function test_evidence_belongs_to_criterion(): void
    {
        $criterion = $this->makeCriterion();

        $evidence = Evidence::factory()->create([
            'criterio_id' => $criterion->criterio_id,
        ]);

        $this->assertSame($criterion->criterio_id, $evidence->criterion->criterio_id);
    }

    // ─── HU-013: relaciones y estados ────────────────────────────────────────

    public function test_evidence_has_many_comments_relacion_polimorfica(): void
    {
        $criterion = $this->makeCriterion();
        $evidence  = Evidence::factory()->create(['criterio_id' => $criterion->criterio_id]);

        Comment::factory()->create([
            'commentable_type' => Evidence::class,
            'commentable_id'   => $evidence->evidencia_id,
        ]);

        $this->assertSame(1, $evidence->comments()->count());
    }

    public function test_evidence_estado_Pendiente_no_es_revisable(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $criterion = $this->makeCriterion();
        $evidence  = Evidence::factory()->create([
            'criterio_id' => $criterion->criterio_id,
            'estado'      => 'Pendiente',
        ]);

        $reviewer = User::factory()->create();
        $service  = app(\App\Services\TradicionalEvidenceService::class);

        $service->retroalimentar($evidence, [
            'estado'     => 'Observada',
            'comentario' => 'Test',
        ], $reviewer);
    }

    public function test_evidence_estado_En_Proceso_cambia_a_Observada_tras_retroalimentar(): void
    {
        $criterion = $this->makeCriterion();
        $evidence  = Evidence::factory()->create([
            'criterio_id' => $criterion->criterio_id,
            'estado'      => 'En Proceso',
        ]);

        $reviewer = User::factory()->create();
        $service  = app(\App\Services\TradicionalEvidenceService::class);

        $updated = $service->retroalimentar($evidence, [
            'estado'     => 'Observada',
            'comentario' => 'Le falta el acta firmada.',
        ], $reviewer);

        $this->assertSame('Observada', $updated->estado);

        $this->assertDatabaseHas('EVIDENCIA', [
            'evidencia_id' => $evidence->evidencia_id,
            'estado'       => 'Observada',
        ]);

        $this->assertDatabaseHas('COMENTARIO', [
            'usuario_id' => $reviewer->usuario_id,
            'texto'      => 'Le falta el acta firmada.',
        ]);
    }
}
