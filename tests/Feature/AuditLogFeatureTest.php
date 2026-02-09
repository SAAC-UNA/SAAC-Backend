<?php

namespace Tests\Feature;

use App\Models\ActionType;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AuditLogFeatureTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function testCanCreateAndRetrieveAuditLog(): void
    {
        $auditLog = AuditLog::factory()->create([
            'detalle' => 'Logout funcional',
        ]);

        $found = AuditLog::where('detalle', 'Logout funcional')->first();
        
        $this->assertNotNull($found);
        $this->assertEquals('Logout funcional', $found->detalle);
        $this->assertEquals($auditLog->bitacora_id, $found->bitacora_id);
    }

    #[Test]
    public function testCanFilterAuditLogsByUser(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        
        AuditLog::factory()->count(3)->create(['usuario_id' => $user1->usuario_id]);
        AuditLog::factory()->count(2)->create(['usuario_id' => $user2->usuario_id]);
        
        $user1Logs = AuditLog::where('usuario_id', $user1->usuario_id)->get();
        $user2Logs = AuditLog::where('usuario_id', $user2->usuario_id)->get();
        
        $this->assertCount(3, $user1Logs);
        $this->assertCount(2, $user2Logs);
    }

    #[Test]
    public function testCanFilterAuditLogsByActionType(): void
    {
        $actionType1 = ActionType::factory()->create(['descripcion' => 'Login']);
        $actionType2 = ActionType::factory()->create(['descripcion' => 'Logout']);
        
        AuditLog::factory()->count(5)->create(['tipo_accion_id' => $actionType1->tipo_accion_id]);
        AuditLog::factory()->count(3)->create(['tipo_accion_id' => $actionType2->tipo_accion_id]);
        
        $loginLogs = AuditLog::where('tipo_accion_id', $actionType1->tipo_accion_id)->get();
        $logoutLogs = AuditLog::where('tipo_accion_id', $actionType2->tipo_accion_id)->get();
        
        $this->assertCount(5, $loginLogs);
        $this->assertCount(3, $logoutLogs);
    }

    #[Test]
    public function testCanEagerLoadRelationships(): void
    {
        $auditLog = AuditLog::factory()->create();
        
        $loaded = AuditLog::with(['user', 'actionType'])
            ->find($auditLog->bitacora_id);
        
        $this->assertTrue($loaded->relationLoaded('user'));
        $this->assertTrue($loaded->relationLoaded('actionType'));
        $this->assertInstanceOf(User::class, $loaded->user);
        $this->assertInstanceOf(ActionType::class, $loaded->actionType);
    }

    #[Test]
    public function testCanCountAuditLogsByUser(): void
    {
        $user = User::factory()->create();
        AuditLog::factory()->count(7)->create(['usuario_id' => $user->usuario_id]);
        
        $count = AuditLog::where('usuario_id', $user->usuario_id)->count();
        
        $this->assertEquals(7, $count);
    }

    #[Test]
    public function testCanOrderAuditLogsByDate(): void
    {
        $log1 = AuditLog::factory()->create(['created_at' => now()->subDays(2)]);
        $log2 = AuditLog::factory()->create(['created_at' => now()->subDays(1)]);
        $log3 = AuditLog::factory()->create(['created_at' => now()]);
        
        $logs = AuditLog::orderBy('created_at', 'desc')->get();
        
        $this->assertEquals($log3->bitacora_id, $logs[0]->bitacora_id);
        $this->assertEquals($log2->bitacora_id, $logs[1]->bitacora_id);
        $this->assertEquals($log1->bitacora_id, $logs[2]->bitacora_id);
    }

    #[Test]
    public function testCanSearchAuditLogsByDetalle(): void
    {
        AuditLog::factory()->create(['detalle' => 'Usuario inició sesión exitosamente']);
        AuditLog::factory()->create(['detalle' => 'Usuario cerró sesión']);
        AuditLog::factory()->create(['detalle' => 'Usuario modificó perfil']);
        
        $loginLogs = AuditLog::where('detalle', 'like', '%sesión%')->get();
        
        $this->assertCount(2, $loginLogs);
    }

    #[Test]
    public function testCanCreateMultipleAuditLogsForSameUser(): void
    {
        $user = User::factory()->create();
        $actionType = ActionType::factory()->create();
        
        $logs = AuditLog::factory()->count(10)->create([
            'usuario_id' => $user->usuario_id,
            'tipo_accion_id' => $actionType->tipo_accion_id,
        ]);
        
        $this->assertCount(10, $logs);
        
        $userLogs = AuditLog::where('usuario_id', $user->usuario_id)->get();
        $this->assertCount(10, $userLogs);
    }
}

