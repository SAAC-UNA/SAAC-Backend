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
        $auditLog = \App\Models\AuditLog::factory()->create();

        $this->assertDatabaseHas('BITACORA', [
            'bitacora_id' => $auditLog->bitacora_id,
        ]);
    }
}
