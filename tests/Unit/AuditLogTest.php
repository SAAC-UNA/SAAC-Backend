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
        $auditLog = AuditLog::factory()->create([
            'accion' => 'Login',
        ]);

        $this->assertDatabaseHas('AUDITORIA', [
            'accion' => 'Login',
        ]);
    }
}
