<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\AuditLog;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_creates_an_audit_log()
    {
        // Prueba de creación de registro de bitácora
        $auditLog = \App\Models\AuditLog::factory()->create();
        $this->assertDatabaseHas('BITACORA', [
            'bitacora_id' => $auditLog->bitacora_id,
        ]);
    }

    /** @test */
    public function it_requires_usuario_id_field()
    {
        // Prueba de validación: campo usuario_id es obligatorio
        $this->expectException(\Illuminate\Database\QueryException::class);
        \App\Models\AuditLog::factory()->create(['usuario_id' => null]);
    }

    /** @test */
    public function it_requires_tipo_accion_id_field()
    {
        // Prueba de validación: campo tipo_accion_id es obligatorio
        $this->expectException(\Illuminate\Database\QueryException::class);
        \App\Models\AuditLog::factory()->create(['tipo_accion_id' => null]);
    }

    /** @test */
    public function it_updates_an_audit_log()
    {
        // Prueba de actualización de registro de bitácora
        $auditLog = \App\Models\AuditLog::factory()->create(['detalle' => 'Original']);
        $auditLog->update(['detalle' => 'Actualizado']);
        $this->assertDatabaseHas('BITACORA', ['detalle' => 'Actualizado']);
    }

    /** @test */
    public function it_deletes_an_audit_log()
    {
        // Prueba de eliminación de registro de bitácora
        $auditLog = \App\Models\AuditLog::factory()->create();
        $auditLog->delete();
        $this->assertDatabaseMissing('BITACORA', ['bitacora_id' => $auditLog->bitacora_id]);
    }

    /** @test */
    public function an_audit_log_belongs_to_user()
    {
        // Prueba de relación belongsTo con User
        $user = \App\Models\User::factory()->create();
        $auditLog = \App\Models\AuditLog::factory()->create(['usuario_id' => $user->usuario_id]);
        $this->assertEquals($user->usuario_id, $auditLog->user->usuario_id);
    }

    /** @test */
    public function an_audit_log_belongs_to_action_type()
    {
        // Prueba de relación belongsTo con ActionType
        $actionType = \App\Models\ActionType::factory()->create();
        $auditLog = \App\Models\AuditLog::factory()->create(['tipo_accion_id' => $actionType->tipo_accion_id]);
        $this->assertEquals($actionType->tipo_accion_id, $auditLog->actionType->tipo_accion_id);
    }
}
