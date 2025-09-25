<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\AuditLog;

class AuditLogFeatureTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_and_retrieve_an_audit_log()
    {
        $auditLog = AuditLog::factory()->create([
            'accion' => 'Logout',
        ]);

        $found = AuditLog::where('accion', 'Logout')->first();
        $this->assertNotNull($found);
        $this->assertEquals('Logout', $found->accion);
    }
}
