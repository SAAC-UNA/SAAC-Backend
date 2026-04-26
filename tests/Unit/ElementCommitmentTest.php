<?php

use App\Models\AccreditationCycle;
use App\Models\CareerCampus;
use App\Models\ElementAssignment;
use App\Models\ElementCommitment;
use App\Models\Process;
use App\Models\StructureElement;
use App\Models\StructureModel;
use App\Services\ElementCommitmentService;
use App\Models\User;

beforeEach(function () {
    $this->service = new ElementCommitmentService();

    [$this->process, $this->rootElement] = elementCommitmentTreeBase();

    $this->childElement = StructureElement::factory()->create([
        'modelo_estructura_id' => $this->rootElement->modelo_estructura_id,
        'padre_id' => $this->rootElement->elemento_id,
        'activo' => true,
    ]);
});

it('lists commitments with pagination', function () {
    ElementCommitment::create([
        'proceso_id' => $this->process->proceso_id,
        'descripcion' => 'Compromiso flexible A',
        'fecha_inicio' => now()->addDay()->toDateString(),
        'fecha_fin' => now()->addDays(10)->toDateString(),
        'estado' => 'Pendiente',
        'activo' => true,
    ]);

    $result = $this->service->listCommitments();

    expect($result->total())->toBe(1);
});

it('gets an existing commitment by id', function () {
    $commitment = ElementCommitment::create([
        'proceso_id' => $this->process->proceso_id,
        'descripcion' => 'Compromiso flexible B',
        'fecha_inicio' => now()->addDay()->toDateString(),
        'fecha_fin' => now()->addDays(12)->toDateString(),
        'estado' => 'Pendiente',
        'activo' => true,
    ]);

    $found = $this->service->getCommitment($commitment->compromiso_elemento_id);

    expect($found)->not->toBeNull();
    expect($found->compromiso_elemento_id)->toBe($commitment->compromiso_elemento_id);
});

it('returns null when commitment does not exist', function () {
    $found = $this->service->getCommitment(999999);

    expect($found)->toBeNull();
});

it('filters commitments by assigned user', function () {
    $user = User::factory()->create();

    $assignment = ElementAssignment::create([
        'elemento_id' => $this->childElement->elemento_id,
        'usuario_id' => $user->usuario_id,
        'proceso_id' => $this->process->proceso_id,
        'asignado_por' => $user->usuario_id,
        'estado' => ElementAssignment::ESTADO_PENDIENTE,
    ]);

    $commitment = ElementCommitment::create([
        'proceso_id' => $this->process->proceso_id,
        'descripcion' => 'Compromiso por usuario',
        'fecha_inicio' => now()->addDay()->toDateString(),
        'fecha_fin' => now()->addDays(15)->toDateString(),
        'estado' => 'Pendiente',
        'activo' => true,
    ]);
    $commitment->assignedElements()->attach($assignment->elemento_asignacion_id);

    $result = $this->service->getCommitmentsByUser($user->usuario_id);

    expect($result->total())->toBe(1);
});

it('filters commitments by element and descendants', function () {
    $user = User::factory()->create();

    $assignment = ElementAssignment::create([
        'elemento_id' => $this->childElement->elemento_id,
        'usuario_id' => $user->usuario_id,
        'proceso_id' => $this->process->proceso_id,
        'asignado_por' => $user->usuario_id,
        'estado' => ElementAssignment::ESTADO_PENDIENTE,
    ]);

    $commitment = ElementCommitment::create([
        'proceso_id' => $this->process->proceso_id,
        'descripcion' => 'Compromiso por arbol',
        'fecha_inicio' => now()->addDay()->toDateString(),
        'fecha_fin' => now()->addDays(20)->toDateString(),
        'estado' => 'Pendiente',
        'activo' => true,
    ]);
    $commitment->assignedElements()->attach($assignment->elemento_asignacion_id);

    $result = $this->service->getCommitmentsByElemento($this->rootElement->elemento_id);

    expect($result->total())->toBe(1);
});

it('creates commitment with element assignments', function () {
    $assignedUser = User::factory()->create();

    $created = $this->service->createCommitment([
        'proceso_id' => $this->process->proceso_id,
        'elemento_id' => $this->rootElement->elemento_id,
        'descripcion' => 'Compromiso creado por servicio',
        'fecha_inicio' => now()->addDay()->toDateString(),
        'fecha_fin' => now()->addDays(30)->toDateString(),
        'estado' => 'Pendiente',
        'elementos_asignar' => [[
            'elemento_id' => $this->childElement->elemento_id,
            'usuarios' => [$assignedUser->usuario_id],
            'comentario' => 'Asignacion inicial',
        ]],
    ]);

    expect($created)->not->toBeNull();

    $this->assertDatabaseHas('COMPROMISO_MEJORA_ELEMENTO', [
        'compromiso_elemento_id' => $created->compromiso_elemento_id,
        'proceso_id' => $this->process->proceso_id,
    ]);

    $assignmentId = ElementAssignment::query()
        ->where('elemento_id', $this->childElement->elemento_id)
        ->where('usuario_id', $assignedUser->usuario_id)
        ->where('proceso_id', $this->process->proceso_id)
        ->value('elemento_asignacion_id');

    expect($assignmentId)->not->toBeNull();

    $this->assertDatabaseHas('COMPROMISO_MEJORA_ELEMENTO_ASIGNACION', [
        'compromiso_elemento_id' => $created->compromiso_elemento_id,
        'elemento_asignacion_id' => $assignmentId,
    ]);
});

it('updates commitment fields when data changed', function () {
    $commitment = ElementCommitment::create([
        'proceso_id' => $this->process->proceso_id,
        'descripcion' => 'Descripcion inicial',
        'fecha_inicio' => now()->addDay()->toDateString(),
        'fecha_fin' => now()->addDays(10)->toDateString(),
        'estado' => 'Pendiente',
        'activo' => true,
    ]);

    $updated = $this->service->updateCommitment($commitment, [
        'descripcion' => 'Descripcion actualizada',
        'estado' => 'En Progreso',
    ]);

    expect($updated)->not->toBeNull();
    expect($updated->descripcion)->toBe('Descripcion actualizada');
    expect($updated->estado)->toBe('En Progreso');
});

it('returns null when update has no effective changes', function () {
    $commitment = ElementCommitment::create([
        'proceso_id' => $this->process->proceso_id,
        'descripcion' => 'Sin cambios',
        'fecha_inicio' => now()->addDay()->toDateString(),
        'fecha_fin' => now()->addDays(10)->toDateString(),
        'estado' => 'Pendiente',
        'activo' => true,
    ]);

    $updated = $this->service->updateCommitment($commitment, [
        'descripcion' => 'Sin cambios',
    ]);

    expect($updated)->toBeNull();
});

it('toggles active state', function () {
    $commitment = ElementCommitment::create([
        'proceso_id' => $this->process->proceso_id,
        'descripcion' => 'Compromiso activo',
        'fecha_inicio' => now()->addDay()->toDateString(),
        'fecha_fin' => now()->addDays(14)->toDateString(),
        'estado' => 'Pendiente',
        'activo' => true,
    ]);

    $updated = $this->service->setActive($commitment, false);

    expect($updated->activo)->toBeFalse();
});

function elementCommitmentTreeBase(): array
{
    $model = StructureModel::factory()->create();
    $careerCampus = CareerCampus::factory()->create();
    $cycle = AccreditationCycle::factory()->create([
        'carrera_sede_id' => $careerCampus->carrera_sede_id,
        'modelo_estructura_id' => $model->modelo_estructura_id,
    ]);

    $process = Process::factory()->create([
        'ciclo_acreditacion_id' => $cycle->ciclo_acreditacion_id,
        'tipo_proceso' => 'Compromiso de mejora',
        'activo' => true,
    ]);

    $root = StructureElement::factory()->create([
        'modelo_estructura_id' => $model->modelo_estructura_id,
        'padre_id' => null,
        'activo' => true,
    ]);

    return [$process, $root];
}
