<?php

namespace Tests\Unit;

use App\Models\ActionType;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function testHasCorrectTableName(): void
    {
        $auditLog = new AuditLog();
        $this->assertEquals('BITACORA', $auditLog->getTable());
    }

    #[Test]
    public function testHasCorrectPrimaryKey(): void
    {
        $auditLog = new AuditLog();
        $this->assertEquals('bitacora_id', $auditLog->getKeyName());
    }

    #[Test]
    public function testHasFillableAttributes(): void
    {
        $auditLog = new AuditLog();
        $expected = ['usuario_id', 'tipo_accion_id', 'detalle', 'fecha_hora'];
        $this->assertEquals($expected, $auditLog->getFillable());
    }

    #[Test]
    public function testCanCreateAuditLog(): void
    {
        $auditLog = AuditLog::factory()->create();
        
        $this->assertDatabaseHas('BITACORA', [
            'bitacora_id' => $auditLog->bitacora_id,
        ]);
    }

    #[Test]
    public function testRequiresUsuarioId(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);
        AuditLog::factory()->create(['usuario_id' => null]);
    }

    #[Test]
    public function testRequiresTipoAccionId(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);
        AuditLog::factory()->create(['tipo_accion_id' => null]);
    }

    #[Test]
    public function testCanUpdateAuditLog(): void
    {
        $auditLog = AuditLog::factory()->create(['detalle' => 'Original']);
        $auditLog->update(['detalle' => 'Actualizado']);
        
        $this->assertDatabaseHas('BITACORA', [
            'bitacora_id' => $auditLog->bitacora_id,
            'detalle' => 'Actualizado'
        ]);
    }

    #[Test]
    public function testCanDeleteAuditLog(): void
    {
        $auditLog = AuditLog::factory()->create();
        $id = $auditLog->bitacora_id;
        
        $auditLog->delete();
        
        $this->assertDatabaseMissing('BITACORA', ['bitacora_id' => $id]);
    }

    #[Test]
    public function testBelongsToUser(): void
    {
        $user = User::factory()->create();
        $auditLog = AuditLog::factory()->create(['usuario_id' => $user->usuario_id]);
        
        $this->assertInstanceOf(User::class, $auditLog->user);
        $this->assertEquals($user->usuario_id, $auditLog->user->usuario_id);
    }

    #[Test]
    public function testBelongsToActionType(): void
    {
        $actionType = ActionType::factory()->create();
        $auditLog = AuditLog::factory()->create(['tipo_accion_id' => $actionType->tipo_accion_id]);
        
        $this->assertInstanceOf(ActionType::class, $auditLog->actionType);
        $this->assertEquals($actionType->tipo_accion_id, $auditLog->actionType->tipo_accion_id);
    }

    #[Test]
    public function testFactoryCreatesValidAuditLog(): void
    {
        $auditLog = AuditLog::factory()->create();
        
        $this->assertNotNull($auditLog->bitacora_id);
        $this->assertNotNull($auditLog->usuario_id);
        $this->assertNotNull($auditLog->tipo_accion_id);
        $this->assertIsString($auditLog->detalle);
    }

    #[Test]
    public function testCanCreateAuditLogWithSpecificDetalle(): void
    {
        $detalle = 'Usuario inició sesión exitosamente';
        $auditLog = AuditLog::factory()->create(['detalle' => $detalle]);
        
        $this->assertEquals($detalle, $auditLog->detalle);
    }
}

