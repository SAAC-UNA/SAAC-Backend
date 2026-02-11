<?php

use App\Models\ActionType;
use App\Models\AuditLog;
use App\Models\User;

it('has correct table name', function () {
    $auditLog = new AuditLog();
    expect($auditLog->getTable())->toBe('BITACORA');
});

it('has correct primary key', function () {
    $auditLog = new AuditLog();
    expect($auditLog->getKeyName())->toBe('bitacora_id');
});

it('has fillable attributes', function () {
    $auditLog = new AuditLog();
    $expected = ['usuario_id', 'tipo_accion_id', 'modulo', 'detalle', 'fecha_hora'];
    expect($auditLog->getFillable())->toBe($expected);
});

it('can create audit log', function () {
    $auditLog = AuditLog::factory()->create();
    
    $this->assertDatabaseHas('BITACORA', [
        'bitacora_id' => $auditLog->bitacora_id,
    ]);
});

it('allows nullable usuario_id', function () {
    $actionType = ActionType::factory()->create();
    $auditLog = AuditLog::factory()->create([
        'usuario_id' => null,
        'tipo_accion_id' => $actionType->tipo_accion_id,
    ]);
    
    expect($auditLog->usuario_id)->toBeNull();
    $this->assertDatabaseHas('BITACORA', [
        'bitacora_id' => $auditLog->bitacora_id,
        'usuario_id' => null,
    ]);
});

it('requires tipo_accion_id', function () {
    AuditLog::factory()->create(['tipo_accion_id' => null]);
})->throws(\Illuminate\Database\QueryException::class);

it('can update audit log', function () {
    $auditLog = AuditLog::factory()->create(['detalle' => 'Original']);
    $auditLog->update(['detalle' => 'Actualizado']);
    
    $this->assertDatabaseHas('BITACORA', [
        'bitacora_id' => $auditLog->bitacora_id,
        'detalle' => 'Actualizado'
    ]);
});

it('can delete audit log', function () {
    $auditLog = AuditLog::factory()->create();
    $id = $auditLog->bitacora_id;
    
    $auditLog->delete();
    
    $this->assertDatabaseMissing('BITACORA', ['bitacora_id' => $id]);
});

it('belongs to user', function () {
    $user = User::factory()->create();
    $auditLog = AuditLog::factory()->create(['usuario_id' => $user->usuario_id]);
    
    $this->assertInstanceOf(User::class, $auditLog->user);
    expect($auditLog->user->usuario_id)->toBe($user->usuario_id);
});

it('belongs to action type', function () {
    $actionType = ActionType::factory()->create();
    $auditLog = AuditLog::factory()->create(['tipo_accion_id' => $actionType->tipo_accion_id]);
    
    $this->assertInstanceOf(ActionType::class, $auditLog->actionType);
    expect($auditLog->actionType->tipo_accion_id)->toBe($actionType->tipo_accion_id);
});

it('factory creates valid audit log', function () {
    $auditLog = AuditLog::factory()->create();
    
    $this->assertNotNull($auditLog->bitacora_id);
    $this->assertNotNull($auditLog->usuario_id);
    $this->assertNotNull($auditLog->tipo_accion_id);
    $this->assertIsString($auditLog->detalle);
});

it('can create audit log with specific detalle', function () {
    $detalle = 'Usuario inició sesión exitosamente';
    $auditLog = AuditLog::factory()->create(['detalle' => $detalle]);
    
    expect($auditLog->detalle)->toBe($detalle);
});

