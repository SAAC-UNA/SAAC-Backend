<?php

use App\Models\AccreditationCycle;
use App\Models\Campus;
use App\Models\Career;
use App\Models\CareerCampus;
use App\Models\Component;
use App\Models\Criterion;
use App\Models\CriterionApproval;
use App\Models\Dimension;
use App\Models\Evidence;
use App\Models\EvidenceAssignment;
use App\Models\Process;
use App\Models\User;
use App\Services\CriterionApprovalService;

function criterionApprovalServiceContext(): array
{
    $service = new CriterionApprovalService();

    $dimension = Dimension::factory()->create();
    $component = Component::factory()->create(['dimension_id' => $dimension->dimension_id]);
    $criterion = Criterion::factory()->create(['componente_id' => $component->componente_id]);

    $career = Career::factory()->create();
    $campus = Campus::factory()->create();
    $careerCampus = CareerCampus::factory()->create([
        'carrera_id' => $career->carrera_id,
        'sede_id' => $campus->sede_id,
    ]);
    $cycle = AccreditationCycle::factory()->create([
        'carrera_sede_id' => $careerCampus->carrera_sede_id,
    ]);
    $process = Process::factory()->create([
        'ciclo_acreditacion_id' => $cycle->ciclo_acreditacion_id,
    ]);

    $user = User::factory()->create();
    $evidence = Evidence::factory()->create([
        'criterio_id' => $criterion->criterio_id,
    ]);

    return [$service, $criterion, $process, $user, $evidence];
}

it('gets approval by criterion and process', function () {
    [$service, $criterion, $process, $user] = criterionApprovalServiceContext();

    $approval = CriterionApproval::create([
        'criterio_id' => $criterion->criterio_id,
        'proceso_id' => $process->proceso_id,
        'usuario_id' => $user->usuario_id,
        'estado' => 'aprobado',
    ]);

    $found = $service->getApprovalByCriterionAndProcess(
        $criterion->criterio_id,
        $process->proceso_id
    );

    expect($found)->not->toBeNull();
    expect($found->aprobacion_criterio_id)->toBe($approval->aprobacion_criterio_id);
});

it('approves an individual evidence and creates block approval', function () {
    [$service, $criterion, $process, $user, $evidence] = criterionApprovalServiceContext();

    $result = $service->approveIndividualEvidence(
        $criterion->criterio_id,
        $evidence->evidencia_id,
        $process->proceso_id,
        $user->usuario_id
    );

    expect($result['criterion_approval'])->not->toBeNull();
    expect($result['evidence_approval'])->not->toBeNull();
    expect($result['evidence_approval']->estado)->toBe('aprobado');

    $this->assertDatabaseHas('APROBACION_CRITERIO', [
        'criterio_id' => $criterion->criterio_id,
        'proceso_id' => $process->proceso_id,
    ]);

    $this->assertDatabaseHas('APROBACION_EVIDENCIA', [
        'evidencia_id' => $evidence->evidencia_id,
        'proceso_id' => $process->proceso_id,
        'usuario_id' => $user->usuario_id,
        'estado' => 'aprobado',
    ]);
});

it('rejects an individual evidence and updates assignment data', function () {
    [$service, $criterion, $process, $user, $evidence] = criterionApprovalServiceContext();

    EvidenceAssignment::create([
        'evidencia_id' => $evidence->evidencia_id,
        'usuario_id' => $user->usuario_id,
        'proceso_id' => $process->proceso_id,
        'estado' => EvidenceAssignment::ESTADO_EN_PROGRESO,
        'fecha_asignacion' => now(),
    ]);

    $deadline = now()->addDays(7)->toDateString();

    $result = $service->rejectIndividualEvidence(
        $criterion->criterio_id,
        $evidence->evidencia_id,
        $process->proceso_id,
        $user->usuario_id,
        'Corregir sustento',
        $deadline
    );

    expect($result['criterion_approval'])->not->toBeNull();
    expect($result['evidence_approval'])->not->toBeNull();
    expect($result['evidence_approval']->estado)->toBe('rechazado');

    $this->assertDatabaseHas('APROBACION_EVIDENCIA', [
        'evidencia_id' => $evidence->evidencia_id,
        'proceso_id' => $process->proceso_id,
        'usuario_id' => $user->usuario_id,
        'estado' => 'rechazado',
        'comentario' => 'Corregir sustento',
    ]);

    $this->assertDatabaseHas('EVIDENCIA_ASIGNACION', [
        'evidencia_id' => $evidence->evidencia_id,
        'proceso_id' => $process->proceso_id,
        'usuario_id' => $user->usuario_id,
        'estado' => EvidenceAssignment::ESTADO_PENDIENTE,
        'comentario' => 'Corregir sustento',
    ]);
});

it('lists individual evidence approvals for a criterion', function () {
    [$service, $criterion, $process, $user, $evidence] = criterionApprovalServiceContext();

    EvidenceAssignment::create([
        'evidencia_id' => $evidence->evidencia_id,
        'usuario_id' => $user->usuario_id,
        'proceso_id' => $process->proceso_id,
        'estado' => EvidenceAssignment::ESTADO_PENDIENTE,
        'fecha_asignacion' => now(),
    ]);

    $service->approveIndividualEvidence(
        $criterion->criterio_id,
        $evidence->evidencia_id,
        $process->proceso_id,
        $user->usuario_id
    );

    $result = $service->listEvidenceApprovals(
        $criterion->criterio_id,
        $process->proceso_id
    );

    expect($result)->toHaveKeys(['criterion_approval', 'evidences']);
    expect($result['evidences'])->toHaveCount(1);
    expect($result['evidences'][0]['evidencia_id'])->toBe($evidence->evidencia_id);
    expect($result['evidences'][0]['approval_status'])->toBe('pendiente');
});
