<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\EvidenceAssignment;
use App\Models\Evidence;
use App\Models\Process;
use Illuminate\Foundation\Testing\RefreshDatabase;

class EvidenceAssignmentApiTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Ejecutar seeders para tener datos de prueba
        $this->artisan('db:seed --class=PermissionSeeder');
        $this->artisan('db:seed --class=UserSeeder');
        $this->artisan('db:seed --class=EvidenceAssignmentSeederFixed');
        
        // Autenticar usuario para las pruebas API
        $this->user = User::first();
        $this->actingAs($this->user, 'api');
    }

    public function test_can_list_evidence_assignments()
    {
        $response = $this->getJson('/api/evidencias-asignaciones');
        
        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'id',
                            'proceso_id',
                            'evidencia_id', 
                            'usuario_id',
                            'estado',
                            'fecha_asignacion',
                            'fecha_limite'
                        ]
                    ]
                ]);
    }

    public function test_can_create_evidence_assignment()
    {
        $process = Process::first();
        $evidence = Evidence::first();
        $user = User::skip(1)->first();
        
        $data = [
            'proceso_id' => $process->proceso_id,
            'evidencia_id' => $evidence->evidencia_id,
            'usuario_id' => $user->usuario_id,
            'estado' => 'pendiente',
            'fecha_limite' => now()->addDays(15)->toDateString()
        ];

        $response = $this->postJson('/api/evidencias-asignaciones', $data);
        
        $response->assertStatus(201);
        
        $this->assertDatabaseHas('EVIDENCIA_ASIGNACION', [
            'proceso_id' => $data['proceso_id'],
            'evidencia_id' => $data['evidencia_id'],
            'usuario_id' => $data['usuario_id'],
            'estado' => $data['estado']
        ]);
    }

    public function test_can_show_specific_evidence_assignment()
    {
        $assignment = EvidenceAssignment::first();
        
        $response = $this->getJson("/api/evidencias-asignaciones/{$assignment->id}");
        
        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        'id',
                        'proceso_id',
                        'evidencia_id',
                        'usuario_id',
                        'estado',
                        'fecha_asignacion',
                        'fecha_limite'
                    ]
                ]);
    }

    public function test_can_update_evidence_assignment()
    {
        $assignment = EvidenceAssignment::first();
        
        $data = [
            'estado' => 'en_progreso'
        ];

        $response = $this->putJson("/api/evidencias-asignaciones/{$assignment->id}", $data);
        
        $response->assertStatus(200);
        
        $this->assertDatabaseHas('EVIDENCIA_ASIGNACION', [
            'id' => $assignment->id,
            'estado' => 'en_progreso'
        ]);
    }

    public function test_can_get_assignments_by_user()
    {
        $user = User::first();
        
        $response = $this->getJson("/api/usuarios/{$user->usuario_id}/evidencias-asignadas");
        
        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'id',
                            'proceso_id',
                            'evidencia_id',
                            'usuario_id',
                            'estado'
                        ]
                    ]
                ]);
    }

    public function test_can_get_assignments_by_evidence()
    {
        $evidence = Evidence::first();
        
        $response = $this->getJson("/api/evidencias/{$evidence->evidencia_id}/asignaciones");
        
        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'id',
                            'proceso_id',
                            'evidencia_id',
                            'usuario_id',
                            'estado'
                        ]
                    ]
                ]);
    }

    public function test_can_get_assignments_by_process()
    {
        $process = Process::first();
        
        $response = $this->getJson("/api/procesos/{$process->proceso_id}/asignaciones");
        
        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'id',
                            'proceso_id',
                            'evidencia_id',
                            'usuario_id',
                            'estado'
                        ]
                    ]
                ]);
    }

    public function test_evidence_assignment_service_works()
    {
        $service = app(\App\Services\EvidenceAssignmentService::class);
        
        $process = Process::first();
        $evidence = Evidence::first();
        $user = User::first();
        
        $assignment = $service->assignEvidence([
            'proceso_id' => $process->proceso_id,
            'evidencia_id' => $evidence->evidencia_id,
            'usuario_id' => $user->usuario_id,
            'estado' => 'pendiente',
            'fecha_limite' => now()->addDays(30)->toDateString()
        ]);
        
        $this->assertNotNull($assignment);
        $this->assertEquals($process->proceso_id, $assignment->proceso_id);
        $this->assertEquals($evidence->evidencia_id, $assignment->evidencia_id);
        $this->assertEquals($user->usuario_id, $assignment->usuario_id);
    }
}