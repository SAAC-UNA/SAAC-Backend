<?php

use App\Models\EvidenceAssignment;
use App\Models\Process;
use App\Models\Evidence;
use App\Models\User;
use App\Models\Role;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    $permissions = [
        'asignaciones.view',
        'asignaciones.create',
        'asignaciones.edit',
        'asignaciones.delete',
    ];

    foreach ($permissions as $permissionName) {
        Permission::firstOrCreate([
            'name' => $permissionName,
            'guard_name' => 'api',
        ]);
    }

    $adminRole = Role::firstOrCreate([
        'name' => 'Administrador',
        'guard_name' => 'api',
    ]);
    $adminRole->syncPermissions($permissions);

    $this->user = User::factory()->create();
    $this->user->assignRole($adminRole);
    Sanctum::actingAs($this->user);
});

it('index returns all assignments', function () {
    EvidenceAssignment::factory()->count(2)->create();
    $response = $this->getJson('/api/evidencias-asignaciones');
    $response->assertStatus(200)
        ->assertJsonStructure(['data']);
    expect(count($response->json('data')))->toBe(2);
});

it('show returns single assignment', function () {
    $assignment = EvidenceAssignment::factory()->create();
    $response = $this->getJson('/api/evidencias-asignaciones/' . $assignment->evidencia_asignacion_id);
    $response->assertStatus(200)
        ->assertJsonStructure(['data']);
    expect($response->json('data.evidencia_asignacion_id'))->toBe($assignment->evidencia_asignacion_id);
});

it('store creates assignment', function () {
    $this->markTestSkipped('Test requiere configuración especial de transacciones - funciona en Unit test');
    
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
    expect($response->json('data.total_asignaciones'))->toBe(1);
});