<?php

namespace Tests\Feature\Sprint2;

use App\Models\EvidenceAssignment;
use App\Models\Process;
use App\Models\Evidence;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EvidenceAssignmentFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_returns_all_assignments()
    {
        EvidenceAssignment::factory()->count(2)->create();
        $response = $this->getJson('/api/evidencias-asignaciones');
        $response->assertStatus(200)
            ->assertJsonStructure(['data']);
        $this->assertCount(2, $response->json('data'));
    }

    public function test_show_returns_single_assignment()
    {
        $assignment = EvidenceAssignment::factory()->create();
        $response = $this->getJson('/api/evidencias-asignaciones/' . $assignment->evidencia_asignacion_id);
        $response->assertStatus(200)
            ->assertJsonStructure(['data']);
        $this->assertEquals($assignment->evidencia_asignacion_id, $response->json('data.evidencia_asignacion_id'));
    }

    public function test_store_creates_assignment()
    {
        $process = Process::factory()->create();
        $evidence = Evidence::factory()->create();
        $user = User::factory()->create();
        $payload = [
            'proceso_id' => $process->proceso_id,
            'evidencia_id' => $evidence->evidencia_id,
            'usuarios' => [$user->usuario_id],
            'fecha_limite' => now()->addDays(7)->toDateString(),
        ];
        $response = $this->postJson('/api/evidencias-asignaciones', $payload);
        $response->assertStatus(201)
            ->assertJsonStructure(['message', 'data']);
        $this->assertEquals(1, $response->json('data.total_asignaciones'));
    }
}
