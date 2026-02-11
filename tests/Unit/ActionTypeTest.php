<?php

use App\Models\ActionType;

it('creates an action type', function () {
    // Prueba de creación de tipo de acción
    $actionType = ActionType::factory()->create([
        'descripcion' => 'Tipo de Acción',
    ]);
    $this->assertDatabaseHas('TIPO_ACCION', [
        'descripcion' => 'Tipo de Acción',
    ]);
});

it('requires descripcion field', function () {
    // Prueba de validación: campo descripción es obligatorio
    ActionType::factory()->create(['descripcion' => null]);
})->throws(\Illuminate\Database\QueryException::class);

it('updates an action type', function () {
    // Prueba de actualización de tipo de acción
    $actionType = ActionType::factory()->create(['descripcion' => 'Original']);
    $actionType->update(['descripcion' => 'Actualizado']);
    $this->assertDatabaseHas('TIPO_ACCION', ['descripcion' => 'Actualizado']);
});

it('deletes an action type', function () {
    // Prueba de eliminación de tipo de acción
    $actionType = ActionType::factory()->create();
    $actionType->delete();
    $this->assertDatabaseMissing('TIPO_ACCION', ['tipo_accion_id' => $actionType->tipo_accion_id]);
});

it('action type has many audit logs', function () {
    // Prueba de relación hasMany con AuditLog
    $actionType = ActionType::factory()->create();
    $auditLog = \App\Models\AuditLog::factory()->create(['tipo_accion_id' => $actionType->tipo_accion_id]);
    expect($actionType->auditLogs->contains($auditLog))->toBeTrue();
});

it('puede tener múltiples audit logs', function () {
    $actionType = ActionType::factory()->create();
    $auditLog1 = \App\Models\AuditLog::factory()->create(['tipo_accion_id' => $actionType->tipo_accion_id]);
    $auditLog2 = \App\Models\AuditLog::factory()->create(['tipo_accion_id' => $actionType->tipo_accion_id]);
    
    expect($actionType->auditLogs)->toHaveCount(2);
});

