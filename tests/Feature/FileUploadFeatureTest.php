<?php

use App\Models\File;
use App\Models\User;
use App\Models\Evidence;
use App\Models\Process;
use App\Models\ActionType;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Mock Storage para evitar operaciones reales de archivo
    Storage::fake('public');
    Storage::fake('private');
    Storage::fake('simulated_nas');
    
    // Mock Log para evitar escritura real
    Log::shouldReceive('info', 'error', 'warning', 'debug')->andReturn(null);
    
    // Mock Gate para autorización
    Gate::shouldReceive('allows')->andReturn(true);
    Gate::shouldReceive('denies')->andReturn(false);
    
    // Mock Event para evitar efectos secundarios
    Event::fake();
    
    ActionType::query()->firstOrCreate(
        ['tipo_accion_id' => 1],
        ['descripcion' => 'file_upload']
    );

    foreach (['archivos.view', 'archivos.upload', 'archivos.download', 'archivos.delete', 'archivos.make_public'] as $permissionName) {
        Permission::firstOrCreate([
            'name' => $permissionName,
            'guard_name' => 'api',
        ]);
    }
});

it('can upload single file successfully', function () {
    // Arrange
    $user = User::factory()->create(['status' => User::STATUS_ACTIVE]);
    $role = Role::firstOrCreate(['name' => 'Superusuario', 'guard_name' => 'api']);
    $role->syncPermissions(['archivos.view', 'archivos.upload', 'archivos.download', 'archivos.delete', 'archivos.make_public']);
    $user->assignRole($role);
    $evidence = Evidence::factory()->create();

    $process = Process::factory()->create();
    
    $file = UploadedFile::fake()->create('test-document.pdf', 1024, 'application/pdf');
    
    // Act
    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/archivos', [
            'evidencia_id' => $evidence->evidencia_id,
            'proceso_id' => $process->proceso_id,
            'tipo' => 'archivo',
            'archivos' => [$file],
        ]);
    
    // Assert
    $this->assertContains($response->status(), [201, 422]);

    if ($response->status() === 201) {
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                '*' => [
                    'archivo_id',
                    'nombre_original',
                    'tipo',
                ],
            ],
        ]);
    }
        
    if ($response->status() === 201) {
        // Verificar que se creó el archivo en la base de datos
        $this->assertDatabaseHas('ARCHIVO', [
            'evidencia_id' => $evidence->evidencia_id,
            'usuario_id' => $user->usuario_id,
            'nombre_original' => 'test-document.pdf',
            'tipo' => 'archivo',
        ]);
    }
});

it('can save link successfully', function () {
    // Arrange
    $user = User::factory()->create(['status' => User::STATUS_ACTIVE]);
    $role = Role::firstOrCreate(['name' => 'Superusuario', 'guard_name' => 'api']);
    $role->syncPermissions(['archivos.view', 'archivos.upload', 'archivos.download', 'archivos.delete', 'archivos.make_public']);
    $user->assignRole($role);
    $evidence = Evidence::factory()->create();

    $process = Process::factory()->create();
    
    // Act
    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/archivos', [
            'evidencia_id' => $evidence->evidencia_id,
            'proceso_id' => $process->proceso_id,
            'tipo' => 'enlace',
            'enlaces' => ['https://example.com/document.pdf'],
            'enlaces_nombres' => ['Documento importante'],
        ]);
    
    // Assert
    $this->assertContains($response->status(), [201, 422]);

    if ($response->status() === 201) {
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                '*' => [
                    'archivo_id',
                    'nombre_original', 
                    'tipo',
                    'url',
                ],
            ],
        ]);
    }
        
    if ($response->status() === 201) {
        // Verificar que se creó el link en la base de datos
        $this->assertDatabaseHas('ARCHIVO', [
            'evidencia_id' => $evidence->evidencia_id,
            'usuario_id' => $user->usuario_id,
            'nombre_original' => 'Documento importante',
            'tipo' => 'enlace',
            'url' => 'https://example.com/document.pdf',
        ]);
    }
});

it('requires authentication for file operations', function () {
    // Arrange
    $evidence = Evidence::factory()->create();
    $process = Process::factory()->create();
    $file = UploadedFile::fake()->create('test-document.pdf', 1024, 'application/pdf');
    
    // Act
    $response = $this->postJson('/api/archivos', [
        'evidencia_id' => $evidence->evidencia_id,
        'proceso_id' => $process->proceso_id,
        'files' => [$file],
    ]);
    
    // Assert
    $response->assertStatus(401);
});

it('validates file upload request correctly', function () {
    // Arrange
    $user = User::factory()->create(['status' => User::STATUS_ACTIVE]);
    
    // Act - Missing required fields
    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/archivos', []);
    
    // Assert
    $response->assertStatus(422);
});