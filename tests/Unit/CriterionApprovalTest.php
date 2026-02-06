<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\CriterionApproval;
use App\Models\Criterion;
use App\Models\Process;
use App\Models\User;
use App\Models\Component;
use App\Models\Dimension;
use App\Models\AccreditationCycle;
use App\Models\CareerCampus;
use App\Models\Career;
use App\Models\Campus;
use PHPUnit\Framework\Attributes\Test;

class CriterionApprovalTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_creates_a_criterion_approval()
    {
        $approval = $this->createApprovalWithData();

        $this->assertDatabaseHas('APROBACION_CRITERIO', [
            'aprobacion_criterio_id' => $approval->aprobacion_criterio_id,
            'estado' => 'aprobado'
        ]);
    }

    #[Test]
    public function it_requires_criterio_id_field()
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        CriterionApproval::create([
            'criterio_id' => null,
            'proceso_id' => 1,
            'usuario_id' => 1,
            'estado' => 'aprobado'
        ]);
    }

    #[Test]
    public function it_requires_proceso_id_field()
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        CriterionApproval::create([
            'criterio_id' => 1,
            'proceso_id' => null,
            'usuario_id' => 1,
            'estado' => 'aprobado'
        ]);
    }

    #[Test]
    public function it_requires_usuario_id_field()
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        CriterionApproval::create([
            'criterio_id' => 1,
            'proceso_id' => 1,
            'usuario_id' => null,
            'estado' => 'aprobado'
        ]);
    }

    #[Test]
    public function it_updates_approval_status()
    {
        $approval = $this->createApprovalWithData();

        $approval->update(['estado' => 'rechazado']);

        $this->assertDatabaseHas('APROBACION_CRITERIO', [
            'aprobacion_criterio_id' => $approval->aprobacion_criterio_id,
            'estado' => 'rechazado'
        ]);
    }

    #[Test]
    public function it_deletes_an_approval()
    {
        $approval = $this->createApprovalWithData();
        $approvalId = $approval->aprobacion_criterio_id;

        $approval->delete();

        $this->assertDatabaseMissing('APROBACION_CRITERIO', [
            'aprobacion_criterio_id' => $approvalId
        ]);
    }

    #[Test]
    public function an_approval_belongs_to_criterion()
    {
        $approval = $this->createApprovalWithData();

        $this->assertInstanceOf(Criterion::class, $approval->criterion);
        $this->assertEquals($approval->criterio_id, $approval->criterion->criterio_id);
    }

    #[Test]
    public function an_approval_belongs_to_process()
    {
        $approval = $this->createApprovalWithData();

        $this->assertInstanceOf(Process::class, $approval->process);
        $this->assertEquals($approval->proceso_id, $approval->process->proceso_id);
    }

    #[Test]
    public function an_approval_belongs_to_user()
    {
        $approval = $this->createApprovalWithData();

        $this->assertInstanceOf(User::class, $approval->user);
        $this->assertEquals($approval->usuario_id, $approval->user->usuario_id);
    }

    #[Test]
    public function it_can_have_commentary()
    {
        $approval = $this->createApprovalWithData([
            'comentario' => 'Este es un comentario de prueba'
        ]);

        $this->assertEquals('Este es un comentario de prueba', $approval->comentario);
    }

    #[Test]
    public function it_has_approved_state()
    {
        $approval = $this->createApprovalWithData([
            'estado' => 'aprobado'
        ]);

        $this->assertEquals('aprobado', $approval->estado);
    }

    #[Test]
    public function it_has_rejected_state()
    {
        $approval = $this->createApprovalWithData([
            'estado' => 'rechazado'
        ]);

        $this->assertEquals('rechazado', $approval->estado);
    }

    // ========== HELPERS ==========

    protected function createApprovalWithData(array $overrides = []): CriterionApproval
    {
        $dimension = Dimension::factory()->create();
        $component = Component::factory()->create(['dimension_id' => $dimension->dimension_id]);
        $criterion = Criterion::factory()->create(['componente_id' => $component->componente_id]);

        $career = Career::factory()->create();
        $campus = Campus::factory()->create();
        $careerCampus = CareerCampus::factory()->create([
            'carrera_id' => $career->carrera_id,
            'sede_id' => $campus->sede_id
        ]);
        $cycle = AccreditationCycle::factory()->create([
            'carrera_sede_id' => $careerCampus->carrera_sede_id
        ]);
        $process = Process::factory()->create([
            'ciclo_acreditacion_id' => $cycle->ciclo_acreditacion_id
        ]);

        $user = User::factory()->create();

        return CriterionApproval::create(array_merge([
            'criterio_id' => $criterion->criterio_id,
            'proceso_id' => $process->proceso_id,
            'usuario_id' => $user->usuario_id,
            'estado' => 'aprobado',
            'comentario' => 'Test approval'
        ], $overrides));
    }
}
