<?php

use App\Models\AccreditationCycle;
use App\Models\Autoevaluation;
use App\Models\ImprovementCommitment;
use App\Models\Process;
use App\Services\FileStorageFactory;
use App\Services\ProcessService;

/** Retorna [servicio, ciclo] frescos para cada test */
function processSetup(): array
{
    return [
        new ProcessService(app(FileStorageFactory::class)),
        AccreditationCycle::factory()->create(),
    ];
}

// --- CRUD ---

it('crea y recupera un proceso', function () {
    [$svc, $cycle] = processSetup();
    $process = $svc->create(['ciclo_acreditacion_id' => $cycle->ciclo_acreditacion_id, 'tipo_proceso' => 'Autoevaluación']);

    expect($process->proceso_id)->toBeInt();
    expect(Process::find($process->proceso_id))->not->toBeNull();
});

it('lista todos los procesos', function () {
    [$svc, $cycle] = processSetup();
    Process::factory()->count(3)->create(['ciclo_acreditacion_id' => $cycle->ciclo_acreditacion_id]);

    expect($svc->getAll())->toHaveCount(3);
});

it('encuentra proceso por id', function () {
    [$svc, $cycle] = processSetup();
    $process = Process::factory()->create(['ciclo_acreditacion_id' => $cycle->ciclo_acreditacion_id]);

    expect($svc->findById($process->proceso_id)->proceso_id)->toBe($process->proceso_id);
});

it('lanza excepcion si proceso no existe', function () {
    [$svc] = processSetup();
    $svc->findById(999999);
})->throws(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

it('actualiza un proceso', function () {
    [$svc, $cycle] = processSetup();
    $process = Process::factory()->create(['ciclo_acreditacion_id' => $cycle->ciclo_acreditacion_id]);

    $updated = $svc->update($process, ['tipo_proceso' => 'Compromiso de mejora']);

    expect($updated->tipo_proceso)->toBe('Compromiso de mejora');
    expect(Process::find($process->proceso_id)->tipo_proceso)->toBe('Compromiso de mejora');
});

it('elimina un proceso', function () {
    [$svc, $cycle] = processSetup();
    $process = Process::factory()->create(['ciclo_acreditacion_id' => $cycle->ciclo_acreditacion_id]);
    $id = $process->proceso_id;

    $svc->delete($process);

    expect(Process::find($id))->toBeNull();
});

// --- toggleActive ---

it('activa un proceso', function () {
    [$svc, $cycle] = processSetup();
    $process = Process::factory()->create(['ciclo_acreditacion_id' => $cycle->ciclo_acreditacion_id, 'activo' => false]);

    $svc->toggleActive($process, true);

    expect($process->fresh()->activo)->toBeTrue();
});

it('desactiva un proceso', function () {
    [$svc, $cycle] = processSetup();
    $process = Process::factory()->create(['ciclo_acreditacion_id' => $cycle->ciclo_acreditacion_id, 'activo' => true]);

    $svc->toggleActive($process, false);

    expect($process->fresh()->activo)->toBeFalse();
});

it('lanza excepcion al activar cuando ya hay otro activo del mismo tipo', function () {
    [$svc, $cycle] = processSetup();
    Process::factory()->create(['ciclo_acreditacion_id' => $cycle->ciclo_acreditacion_id, 'tipo_proceso' => 'Autoevaluación', 'activo' => true]);
    $second = Process::factory()->create(['ciclo_acreditacion_id' => $cycle->ciclo_acreditacion_id, 'tipo_proceso' => 'Autoevaluación', 'activo' => false]);

    $svc->toggleActive($second, true);
})->throws(\InvalidArgumentException::class);

// --- Relaciones y filtros ---

it('carga relacion con ciclo de acreditacion', function () {
    [, $cycle] = processSetup();
    $process = Process::with('accreditationCycle')
        ->find(Process::factory()->create(['ciclo_acreditacion_id' => $cycle->ciclo_acreditacion_id])->proceso_id);

    expect($process->accreditationCycle->ciclo_acreditacion_id)->toBe($cycle->ciclo_acreditacion_id);
});

it('filtra procesos por ciclo', function () {
    [, $cycle] = processSetup();
    $other = AccreditationCycle::factory()->create();
    Process::factory()->count(2)->create(['ciclo_acreditacion_id' => $cycle->ciclo_acreditacion_id]);
    Process::factory()->count(3)->create(['ciclo_acreditacion_id' => $other->ciclo_acreditacion_id]);

    expect(Process::where('ciclo_acreditacion_id', $cycle->ciclo_acreditacion_id)->count())->toBe(2);
});

// --- getDeleteSummary ---

it('retorna resumen de datos asociados antes de eliminar', function () {
    [$svc, $cycle] = processSetup();
    $process = Process::factory()->create(['ciclo_acreditacion_id' => $cycle->ciclo_acreditacion_id]);
    Autoevaluation::factory()->create(['proceso_id' => $process->proceso_id]);
    ImprovementCommitment::factory()->create(['proceso_id' => $process->proceso_id]);

    $summary = $svc->getDeleteSummary($process);

    expect($summary['autoevaluaciones'])->toBe(1)
        ->and($summary['compromisos_mejora'])->toBe(1)
        ->and($summary['asignaciones_evidencia'])->toBe(0);
});
