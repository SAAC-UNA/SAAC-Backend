<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\ActionType;

class ActionTypeTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_creates_an_action_type()
    {
        // Prueba de creación de tipo de acción
        $actionType = ActionType::factory()->create([
            'descripcion' => 'Tipo de Acción',
        ]);
        $this->assertDatabaseHas('TIPO_ACCION', [
            'descripcion' => 'Tipo de Acción',
        ]);
    }

    /** @test */
    public function it_requires_descripcion_field()
    {
        // Prueba de validación: campo descripción es obligatorio
        $this->expectException(\Illuminate\Database\QueryException::class);
        ActionType::factory()->create(['descripcion' => null]);
    }

    /** @test */
    public function it_updates_an_action_type()
    {
        // Prueba de actualización de tipo de acción
        $actionType = ActionType::factory()->create(['descripcion' => 'Original']);
        $actionType->update(['descripcion' => 'Actualizado']);
        $this->assertDatabaseHas('TIPO_ACCION', ['descripcion' => 'Actualizado']);
    }

    /** @test */
    public function it_deletes_an_action_type()
    {
        // Prueba de eliminación de tipo de acción
        $actionType = ActionType::factory()->create();
        $actionType->delete();
        $this->assertDatabaseMissing('TIPO_ACCION', ['tipo_accion_id' => $actionType->tipo_accion_id]);
    }

    /** @test */
    public function an_action_type_has_many_audit_logs()
    {
        // Prueba de relación hasMany con AuditLog
        $actionType = ActionType::factory()->create();
        $auditLog = \App\Models\AuditLog::factory()->create(['tipo_accion_id' => $actionType->tipo_accion_id]);
        $this->assertTrue($actionType->auditLogs->contains($auditLog));
    }
}
