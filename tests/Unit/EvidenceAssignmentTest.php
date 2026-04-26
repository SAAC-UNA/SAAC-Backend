<?php

use App\Models\EvidenceAssignment;
use App\Services\EvidenceAssignmentService;

it('get all assignments returns collection', function () {
    EvidenceAssignment::factory()->count(3)->create();
    $service = new EvidenceAssignmentService();
    $result = $service->getAll();
    $this->assertCount(3, $result);
});

it('find by id returns assignment or null', function () {
    $assignment = EvidenceAssignment::factory()->create();
    $service = new EvidenceAssignmentService();
    $found = $service->findById($assignment->evidencia_asignacion_id);
    $this->assertNotNull($found);
    expect($found->evidencia_asignacion_id)->toBe($assignment->evidencia_asignacion_id);
    $notFound = $service->findById(999999);
    $this->assertNull($notFound);
});

it('assign evidence creates assignments', function () {
    // Crear un modelo de estructura tradicional
    $modeloTradicional = \App\Models\StructureModel::factory()->create([
        'tipo' => \App\Models\StructureModel::TIPO_TRADICIONAL
    ]);
    // Crear un ciclo con modelo tradicional
    $cycle = \App\Models\AccreditationCycle::factory()->create([
        'modelo_estructura_id' => $modeloTradicional->modelo_estructura_id
    ]);
    $process = \App\Models\Process::factory()->create([
        'ciclo_acreditacion_id' => $cycle->ciclo_acreditacion_id
    ]);
    $evidence = \App\Models\Evidence::factory()->create();
    $user = \App\Models\User::factory()->create();
    $service = new EvidenceAssignmentService();
    $data = [
        'proceso_id' => $process->proceso_id,
        'evidencia_id' => $evidence->evidencia_id,
        'usuarios' => [$user->usuario_id],
        'fecha_limite' => now()->addDays(7),
        'comentario' => 'Este es un comentario de prueba para la asignación.',
    ];
    $result = $service->assignEvidence($data);
    expect($result['total_asignaciones'])->toBe(1);
    $this->assertCount(1, $result['asignaciones']);
    expect($result['total_errores'])->toBe(0);
    expect($result['asignaciones'][0]->comentario)->toBe('Este es un comentario de prueba para la asignación.');
});