<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Process;
use App\Models\EvidenceAssignment;
use App\Models\Evidence;
use App\Models\User;
use App\Services\EvidenceAssignmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class EvidenceAssignmentServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new EvidenceAssignmentService();
    }

    public function test_assign_evidence_creates_assignment()
    {
        // Ejecutar seeders para tener datos
        $this->artisan('db:seed --class=PermissionSeeder');
        $this->artisan('db:seed --class=UserSeeder');
    // $this->artisan('db:seed --class=EvidenceAssignmentSeederFixed');

        $process = Process::first();
        $evidence = Evidence::first();
        $user = User::first();

        $data = [
            'proceso_id' => $process->proceso_id,
            'evidencia_id' => $evidence->evidencia_id,
            'usuario_id' => $user->usuario_id,
            'estado' => 'pendiente',
            'fecha_limite' => now()->addDays(30)->toDateString()
        ];

        $assignment = $this->service->assignEvidence($data);

        $this->assertInstanceOf(EvidenceAssignment::class, $assignment);
        $this->assertEquals($data['proceso_id'], $assignment->proceso_id);
        $this->assertEquals($data['evidencia_id'], $assignment->evidencia_id);
        $this->assertEquals($data['usuario_id'], $assignment->usuario_id);
        $this->assertEquals($data['estado'], $assignment->estado);
    }

    public function test_service_sets_default_values()
    {
        // Ejecutar seeders
        $this->artisan('db:seed --class=PermissionSeeder');
        $this->artisan('db:seed --class=UserSeeder');
    // $this->artisan('db:seed --class=EvidenceAssignmentSeederFixed');

        $process = Process::first();
        $evidence = Evidence::first();
        $user = User::first();

        $data = [
            'proceso_id' => $process->proceso_id,
            'evidencia_id' => $evidence->evidencia_id,
            'usuario_id' => $user->usuario_id
        ];

        $assignment = $this->service->assignEvidence($data);

        // Verificar valores por defecto
        $this->assertEquals('pendiente', $assignment->estado);
        $this->assertNotNull($assignment->fecha_asignacion);
        $this->assertNotNull($assignment->fecha_limite);
    }

    public function test_evidence_assignment_model_relationships()
    {
        // Ejecutar seeders
        $this->artisan('db:seed --class=PermissionSeeder');
        $this->artisan('db:seed --class=UserSeeder');
    // $this->artisan('db:seed --class=EvidenceAssignmentSeederFixed');

        $assignment = EvidenceAssignment::with(['process', 'evidence', 'user'])->first();

        $this->assertNotNull($assignment->process);
        $this->assertNotNull($assignment->evidence);
        $this->assertNotNull($assignment->user);
        
        // Verificar tipos de relaciones
        $this->assertInstanceOf(Process::class, $assignment->process);
        $this->assertInstanceOf(Evidence::class, $assignment->evidence);
        $this->assertInstanceOf(User::class, $assignment->user);
    }
}
