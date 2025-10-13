<?php

namespace Tests\Unit\Sprint2;

use App\Models\EvidenceAssignment;
use App\Services\EvidenceAssignmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EvidenceAssignmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_all_assignments_returns_collection()
    {
        EvidenceAssignment::factory()->count(3)->create();
        $service = new EvidenceAssignmentService();
        $result = $service->getAll();
        $this->assertCount(3, $result);
    }

    public function test_find_by_id_returns_assignment_or_null()
    {
        $assignment = EvidenceAssignment::factory()->create();
        $service = new EvidenceAssignmentService();
        $found = $service->findById($assignment->evidencia_asignacion_id);
        $this->assertNotNull($found);
        $this->assertEquals($assignment->evidencia_asignacion_id, $found->evidencia_asignacion_id);
        $notFound = $service->findById(999999);
        $this->assertNull($notFound);
    }

    public function test_assign_evidence_creates_assignments()
    {
        $process = \App\Models\Process::factory()->create();
        $evidence = \App\Models\Evidence::factory()->create();
        $user = \App\Models\User::factory()->create();
        $service = new EvidenceAssignmentService();
        $data = [
            'proceso_id' => $process->proceso_id,
            'evidencia_id' => $evidence->evidencia_id,
            'usuarios' => [$user->usuario_id],
            'fecha_limite' => now()->addDays(7),
        ];
        $result = $service->assignEvidence($data);
        $this->assertEquals(1, $result['total_asignaciones']);
        $this->assertCount(1, $result['asignaciones']);
        $this->assertEquals(0, $result['total_errores']);
    }
}