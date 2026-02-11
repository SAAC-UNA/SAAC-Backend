<?php

use App\Models\ActionType;
use App\Models\AuditLog;
use App\Models\User;

it('can create and retrieve audit log', function () {
    $auditLog = AuditLog::factory()->create([
        'detalle' => 'Logout funcional',
    ]);

    $found = AuditLog::where('detalle', 'Logout funcional')->first();
    
    expect($found)->not->toBeNull();
    expect($found->detalle)->toBe('Logout funcional');
    expect($found->bitacora_id)->toBe($auditLog->bitacora_id);
});

it('can filter audit logs by user', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    
    AuditLog::factory()->count(3)->create(['usuario_id' => $user1->usuario_id]);
    AuditLog::factory()->count(2)->create(['usuario_id' => $user2->usuario_id]);
    
    $user1Logs = AuditLog::where('usuario_id', $user1->usuario_id)->get();
    $user2Logs = AuditLog::where('usuario_id', $user2->usuario_id)->get();
    
    expect($user1Logs)->toHaveCount(3);
    expect($user2Logs)->toHaveCount(2);
});

it('can filter audit logs by action type', function () {
    $actionType1 = ActionType::factory()->create(['descripcion' => 'Login']);
    $actionType2 = ActionType::factory()->create(['descripcion' => 'Logout']);
    
    AuditLog::factory()->count(5)->create(['tipo_accion_id' => $actionType1->tipo_accion_id]);
    AuditLog::factory()->count(3)->create(['tipo_accion_id' => $actionType2->tipo_accion_id]);
    
    $loginLogs = AuditLog::where('tipo_accion_id', $actionType1->tipo_accion_id)->get();
    $logoutLogs = AuditLog::where('tipo_accion_id', $actionType2->tipo_accion_id)->get();
    
    expect($loginLogs)->toHaveCount(5);
    expect($logoutLogs)->toHaveCount(3);
});

it('can eager load relationships', function () {
    $auditLog = AuditLog::factory()->create();
    
    $loaded = AuditLog::with(['user', 'actionType'])
        ->find($auditLog->bitacora_id);
    
    expect($loaded->relationLoaded('user'))->toBeTrue();
    expect($loaded->relationLoaded('actionType'))->toBeTrue();
    expect($loaded->user)->toBeInstanceOf(User::class);
    expect($loaded->actionType)->toBeInstanceOf(ActionType::class);
});

it('can count audit logs by user', function () {
    $user = User::factory()->create();
    AuditLog::factory()->count(7)->create(['usuario_id' => $user->usuario_id]);
    
    $count = AuditLog::where('usuario_id', $user->usuario_id)->count();
    
    expect($count)->toBe(7);
});

it('can order audit logs by date', function () {
    $log1 = AuditLog::factory()->create(['created_at' => now()->subDays(2)]);
    $log2 = AuditLog::factory()->create(['created_at' => now()->subDays(1)]);
    $log3 = AuditLog::factory()->create(['created_at' => now()]);
    
    $logs = AuditLog::orderBy('created_at', 'desc')->get();
    
    expect($logs[0]->bitacora_id)->toBe($log3->bitacora_id);
    expect($logs[1]->bitacora_id)->toBe($log2->bitacora_id);
    expect($logs[2]->bitacora_id)->toBe($log1->bitacora_id);
});

it('can search audit logs by detalle', function () {
    AuditLog::factory()->create(['detalle' => 'Usuario inició sesión exitosamente']);
    AuditLog::factory()->create(['detalle' => 'Usuario cerró sesión']);
    AuditLog::factory()->create(['detalle' => 'Usuario modificó perfil']);
    
    $loginLogs = AuditLog::where('detalle', 'like', '%sesión%')->get();
    
    expect($loginLogs)->toHaveCount(2);
});

it('can create multiple audit logs for same user', function () {
    $user = User::factory()->create();
    $actionType = ActionType::factory()->create();
    
    $logs = AuditLog::factory()->count(10)->create([
        'usuario_id' => $user->usuario_id,
        'tipo_accion_id' => $actionType->tipo_accion_id,
    ]);
    
    expect($logs)->toHaveCount(10);
    
    $userLogs = AuditLog::where('usuario_id', $user->usuario_id)->get();
    expect($userLogs)->toHaveCount(10);
});

