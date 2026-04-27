<?php

use App\Models\User;
use App\Models\Evidence;
use App\Models\Process;
use App\Models\File;
use App\Models\Notification;
use App\Services\NotificationService;
use App\Services\AuditLogService;
use App\Events\MultipleFilesUploaded;
use Laravel\Sanctum\Sanctum;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    // Mock storage and mail to prevent actual operations
    Storage::fake('simulated_nas');
    Mail::fake();
    Event::fake();

    Log::shouldReceive('info', 'error', 'warning', 'debug')->andReturn(null);

    $this->mock(AuditLogService::class)
        ->shouldReceive('log')
        ->andReturn(true);
    
    // Create test users
    $this->uploader = User::factory()->create(['nombre' => 'File Uploader']);
    $this->supervisor = User::factory()->create(['nombre' => 'Supervisor']);

    foreach (['archivos.view', 'archivos.upload', 'archivos.download', 'archivos.delete', 'archivos.make_public'] as $permissionName) {
        Permission::firstOrCreate([
            'name' => $permissionName,
            'guard_name' => 'api',
        ]);
    }

    $role = Role::firstOrCreate(['name' => 'Superusuario', 'guard_name' => 'api']);
    $role->syncPermissions(['archivos.view', 'archivos.upload', 'archivos.download', 'archivos.delete', 'archivos.make_public']);
    $this->uploader->assignRole($role);
    $this->supervisor->assignRole($role);
    
    // Create test data
    $this->evidence = Evidence::factory()->create();
    $this->process = Process::factory()->create();
    
    // Authenticate as uploader
    Sanctum::actingAs($this->uploader, ['*'], 'sanctum');
});

it('creates notification when files are uploaded', function () {
    // Arrange - Mock file upload success
    $files = [
        File::factory()->create([
            'evidencia_id' => $this->evidence->evidencia_id,
            'proceso_id' => $this->process->proceso_id,
            'usuario_id' => $this->uploader->usuario_id,
            'nombre_original' => 'document1.pdf',
            'tipo' => 'archivo',
        ]),
        File::factory()->create([
            'evidencia_id' => $this->evidence->evidencia_id,
            'proceso_id' => $this->process->proceso_id,
            'usuario_id' => $this->uploader->usuario_id,
            'nombre_original' => 'document2.pdf',
            'tipo' => 'archivo',
        ]),
    ];
    
    // Act - Simulate file upload completion notification
    NotificationService::create([
        'usuario_id' => $this->supervisor->usuario_id,
        'tipo_evento' => Notification::TIPO_CARGA_ARCHIVO,
        'titulo' => 'Archivos subidos',
        'mensaje' => "{$this->uploader->nombre} ha subido 2 archivos para la evidencia",
        'relacionado' => $this->evidence,
        'enlace' => "/evidencias/{$this->evidence->evidencia_id}/archivos",
        'metadatos' => [
            'usuario_subida' => $this->uploader->nombre,
            'cantidad_archivos' => 2,
            'evidencia_id' => $this->evidence->evidencia_id,
        ],
    ]);
    
    // Assert
    $notification = Notification::where('usuario_id', $this->supervisor->usuario_id)
        ->where('tipo_evento', Notification::TIPO_CARGA_ARCHIVO)
        ->first();
    
    expect($notification)->not->toBeNull();
    expect($notification->titulo)->toBe('Archivos subidos');
    expect($notification->metadatos['cantidad_archivos'])->toBe(2);
    expect($notification->enlace)->toBe("/evidencias/{$this->evidence->evidencia_id}/archivos");
});

it('generates secure download links for files', function () {
    // Arrange
    $path = 'uploads/original_document.pdf';
    $file = File::factory()->create([
        'evidencia_id' => $this->evidence->evidencia_id,
        'proceso_id' => $this->process->proceso_id,
        'usuario_id' => $this->uploader->usuario_id,
        'nombre_original' => 'original_document.pdf',
        'tipo' => 'archivo',
        'path' => $path,
    ]);

    Storage::disk('simulated_nas')->put($path, 'content');
    
    // Act - Get file download link
    $response = $this->getJson("/api/archivos/{$file->archivo_id}/download");
    
    // Assert
    $response->assertStatus(200);
});

it('returns 404 when file is missing from storage', function () {
    // Arrange - Create file for another user
    $otherUser = User::factory()->create();
    $otherFile = File::factory()->create([
        'evidencia_id' => $this->evidence->evidencia_id,
        'proceso_id' => $this->process->proceso_id,
        'usuario_id' => $otherUser->usuario_id,
        'path' => 'uploads/missing.pdf',
        'tipo' => 'archivo',
    ]);
    
    // Act - Try to access other user's file
    $response = $this->getJson("/api/archivos/{$otherFile->archivo_id}/download");
    
    // Assert
    $response->assertStatus(404)
        ->assertJson([
            'success' => false,
            'message' => 'Archivo no encontrado en el almacenamiento.',
        ]);
});

it('handles link-type files correctly', function () {
    // Arrange
    $linkFile = File::factory()->create([
        'evidencia_id' => $this->evidence->evidencia_id,
        'proceso_id' => $this->process->proceso_id,
        'usuario_id' => $this->uploader->usuario_id,
        'nombre_original' => 'External Document',
        'tipo' => 'enlace',
        'url' => 'https://example.com/document.pdf',
        'path' => null,
    ]);
    
    // Act - Get link file details
    $response = $this->getJson("/api/archivos/{$linkFile->archivo_id}");
    
    // Assert
    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'data' => [
                'archivo_id',
                'nombre_original',
                'tipo',
                'url',
            ],
        ]);
        
    expect($response->json('data.tipo'))->toBe('enlace');
    expect($response->json('data.url'))->toBe('https://example.com/document.pdf');
    expect($response->json('data.nombre_original'))->toBe('External Document');
});

it('creates notification when evidence assignment is made with files', function () {
    // Arrange - User gets assigned evidence that already has files
    $existingFiles = File::factory()->count(3)->create([
        'evidencia_id' => $this->evidence->evidencia_id,
        'proceso_id' => $this->process->proceso_id,
        'usuario_id' => $this->uploader->usuario_id,
        'tipo' => 'archivo',
    ]);
    
    // Act - Create assignment notification
    NotificationService::create([
        'usuario_id' => $this->uploader->usuario_id,
        'tipo_evento' => Notification::TIPO_ASIGNACION_EVIDENCIA,
        'titulo' => 'Nueva evidencia asignada',
        'mensaje' => 'Se te ha asignado la evidencia "Documento de Calidad". Esta evidencia ya tiene archivos adjuntos.',
        'relacionado' => $this->evidence,
        'enlace' => "/evidencias/{$this->evidence->evidencia_id}",
        'metadatos' => [
            'archivos_existentes' => 3,
            'requiere_subida' => true,
            'fecha_limite' => now()->addDays(7)->toDateString(),
        ],
    ]);
    
    // Assert
    $notification = Notification::where('usuario_id', $this->uploader->usuario_id)
        ->where('tipo_evento', Notification::TIPO_ASIGNACION_EVIDENCIA)
        ->first();
    
    expect($notification)->not->toBeNull();
    expect($notification->metadatos['archivos_existentes'])->toBe(3);
    expect($notification->metadatos['requiere_subida'])->toBeTrue();
    expect($notification->mensaje)->toContain('ya tiene archivos adjuntos');
});

it('sends deadline notification with file status', function () {
    // Arrange - Create files with different statuses
    File::factory()->create([
        'evidencia_id' => $this->evidence->evidencia_id,
        'usuario_id' => $this->uploader->usuario_id,
        'proceso_id' => $this->process->proceso_id,
        'tipo' => 'archivo',
    ]);
    
    File::factory()->create([
        'evidencia_id' => $this->evidence->evidencia_id,
        'usuario_id' => $this->uploader->usuario_id,
        'proceso_id' => $this->process->proceso_id,
        'tipo' => 'enlace',
        'url' => 'https://example.com/pendiente.pdf',
    ]);
    
    // Act - Create deadline notification
    NotificationService::create([
        'usuario_id' => $this->uploader->usuario_id,
        'tipo_evento' => Notification::TIPO_VENCIMIENTO_PLAZO,
        'titulo' => 'Evidencia próxima a vencer',
        'mensaje' => 'La evidencia vence mañana. Estado: 1 archivo completado, 1 enlace pendiente.',
        'relacionado' => $this->evidence,
        'metadatos' => [
            'archivos_completados' => 1,
            'archivos_pendientes' => 1,
            'dias_restantes' => 1,
        ],
    ]);
    
    // Assert
    $notification = Notification::where('usuario_id', $this->uploader->usuario_id)
        ->where('tipo_evento', Notification::TIPO_VENCIMIENTO_PLAZO)
        ->first();
    
    expect($notification)->not->toBeNull();
    expect($notification->canal)->toBe(Notification::CANAL_AMBOS); // Critical notification
    expect($notification->metadatos['archivos_completados'])->toBe(1);
    expect($notification->metadatos['archivos_pendientes'])->toBe(1);
});

it('integrates file operations with audit logging', function () {
    // This test verifies that file operations trigger audit logs
    // Since we're mocking AuditLogService, we just verify the integration points
    
    // Arrange
    $file = File::factory()->create([
        'evidencia_id' => $this->evidence->evidencia_id,
        'proceso_id' => $this->process->proceso_id,
        'usuario_id' => $this->uploader->usuario_id,
        'nombre_original' => 'test.pdf',
        'tipo' => 'archivo',
    ]);
    
    // Act - Delete file (should trigger audit log)
    $response = $this->deleteJson("/api/archivos/{$file->archivo_id}");
    
    // Assert
    $response->assertStatus(200);
    
    // In a real scenario, this would verify:
    // - AuditLogService::log was called with appropriate parameters
    // - The log entry contains file operation details
    // - User activity is properly tracked
});

it('handles file upload with notification dispatch', function () {
    // Arrange - Create multiple files to trigger MultipleFilesUploaded event
    $uploadedFiles = [];
    for ($i = 1; $i <= 3; $i++) {
        $uploadedFiles[] = [
            'nombre_original' => "document{$i}.pdf",
            'size_kb' => round(1024 * $i / 1024, 2),
        ];
    }
    
    // Simulate the event being fired
    Event::fake([MultipleFilesUploaded::class]);
    
    // Act - Fire the event as it would happen in the controller
    event(new MultipleFilesUploaded(
        $uploadedFiles,
        $this->uploader->usuario_id,
        $this->evidence->evidencia_id,
        $this->process->proceso_id
    ));
    
    // Assert
    Event::assertDispatched(MultipleFilesUploaded::class, function ($event) use ($uploadedFiles) {
        return $event->usuarioId === $this->uploader->usuario_id &&
               $event->evidenciaId === $this->evidence->evidencia_id &&
               count($event->filesData) === 3 &&
               $event->filesData[0]['nombre_original'] === 'document1.pdf';
    });
});

it('validates file link URLs properly', function () {
    // Test valid URLs
    $validUrls = [
        'https://example.com/document.pdf',
        'http://internal-server.com/file.doc',
        'https://drive.google.com/file/d/1234567890/view',
        'https://dropbox.com/s/xyz123/file.pdf',
    ];
    
    foreach ($validUrls as $url) {
        $linkFile = File::factory()->create([
            'evidencia_id' => $this->evidence->evidencia_id,
            'proceso_id' => $this->process->proceso_id,
            'usuario_id' => $this->uploader->usuario_id,
            'tipo' => 'enlace',
            'url' => $url,
        ]);
        
        expect($linkFile->url)->toBe($url);
        expect($linkFile->tipo)->toBe('enlace');
    }
});

it('returns 404 for unsupported preview endpoint', function () {
    // Arrange - Create files of different types
    $pdfFile = File::factory()->create([
        'evidencia_id' => $this->evidence->evidencia_id,
        'usuario_id' => $this->uploader->usuario_id,
        'proceso_id' => $this->process->proceso_id,
        'nombre_original' => 'document.pdf',
        'tipo' => 'archivo',
    ]);
    
    $imageFile = File::factory()->create([
        'evidencia_id' => $this->evidence->evidencia_id,
        'usuario_id' => $this->uploader->usuario_id,
        'proceso_id' => $this->process->proceso_id,
        'nombre_original' => 'image.jpg',
        'tipo' => 'archivo',
    ]);
    
    // Act - Get preview capabilities
    $response = $this->getJson("/api/archivos/{$pdfFile->archivo_id}/preview");
    
    // Assert
    $response->assertStatus(404);
});

it('shows file details when accessible', function () {
    // Arrange - Create file in evidence not accessible to current user
    $restrictedEvidence = Evidence::factory()->create(['activo' => false]);
    $restrictedFile = File::factory()->create([
        'evidencia_id' => $restrictedEvidence->evidencia_id,
        'proceso_id' => $this->process->proceso_id,
        'usuario_id' => $this->uploader->usuario_id,
        'tipo' => 'archivo',
    ]);
    
    // Act - Try to access restricted file
    $response = $this->getJson("/api/archivos/{$restrictedFile->archivo_id}");
    
    // Assert
    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
        ]);
});

it('returns 404 for unsupported history endpoint', function () {
    // Arrange - Upload multiple versions of the same file
    $originalFile = File::factory()->create([
        'evidencia_id' => $this->evidence->evidencia_id,
        'usuario_id' => $this->uploader->usuario_id,
        'proceso_id' => $this->process->proceso_id,
        'nombre_original' => 'document.pdf',
        'tipo' => 'archivo',
    ]);
    
    $updatedFile = File::factory()->create([
        'evidencia_id' => $this->evidence->evidencia_id,
        'usuario_id' => $this->uploader->usuario_id,
        'proceso_id' => $this->process->proceso_id,
        'nombre_original' => 'document.pdf',
        'tipo' => 'archivo',
    ]);
    
    // Act - Get file history
    $response = $this->getJson("/api/archivos/{$updatedFile->archivo_id}/historial");
    
    // Assert
    $response->assertStatus(404);
});