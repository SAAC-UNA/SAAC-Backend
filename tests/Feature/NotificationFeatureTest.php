<?php

use App\Models\User;
use App\Models\Evidence;
use App\Models\Notification;
use App\Services\NotificationService;
use App\Services\AuditLogService;
use Laravel\Sanctum\Sanctum;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Event;
use Illuminate\Notifications\AnonymousNotifiable;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    // Mock Mail facade to prevent actual email sending
    Mail::fake();

    // Mock AuditLogService (non-facade) via container
    $this->mock(AuditLogService::class)
        ->shouldReceive('log')
        ->andReturn(true);
    
    // Create authenticated user
    $this->user = User::factory()->create([
        'email' => 'test@example.com',
        'nombre' => 'Test User',
    ]);

    $role = Role::firstOrCreate(['name' => 'SuperUsuario', 'guard_name' => 'api']);
    $this->user->assignRole($role);
    
    Sanctum::actingAs($this->user, ['*'], 'sanctum');
    
    // Create test evidence for relationships
    $this->evidence = Evidence::factory()->create();
});

it('can create internal notification successfully', function () {
    // Act
    $notification = NotificationService::create([
        'usuario_id' => $this->user->usuario_id,
        'tipo_evento' => Notification::TIPO_ASIGNACION_EVIDENCIA,
        'titulo' => 'Nueva evidencia asignada',
        'mensaje' => 'Se te ha asignado la evidencia para completar',
        'enlace' => '/evidencias/123',
        'relacionado' => $this->evidence,
        'metadatos' => ['extra_info' => 'test_data'],
    ]);
    
    // Assert
    expect($notification)->toBeInstanceOf(Notification::class);
    expect($notification->usuario_id)->toBe($this->user->usuario_id);
    expect($notification->tipo_evento)->toBe(Notification::TIPO_ASIGNACION_EVIDENCIA);
    expect($notification->titulo)->toBe('Nueva evidencia asignada');
    expect($notification->mensaje)->toBe('Se te ha asignado la evidencia para completar');
    expect($notification->enlace)->toBe('/evidencias/123');
    expect($notification->canal)->toBe(Notification::CANAL_AMBOS);
    expect($notification->leida)->toBeFalse();
    expect($notification->metadatos['extra_info'])->toBe('test_data');
    expect($notification->relacionado_type)->toBe(Evidence::class);
    expect($notification->relacionado_id)->toBe($this->evidence->evidencia_id);
    
    // Verify notification was saved to database
    $this->assertDatabaseHas('NOTIFICACION', [
        'usuario_id' => $this->user->usuario_id,
        'tipo_evento' => Notification::TIPO_ASIGNACION_EVIDENCIA,
        'titulo' => 'Nueva evidencia asignada',
        'canal' => Notification::CANAL_AMBOS,
    ]);
});

it('can create critical notification with email', function () {
    // Act - Create critical notification (should trigger email)
    $notification = NotificationService::create([
        'usuario_id' => $this->user->usuario_id,
        'tipo_evento' => Notification::TIPO_VENCIMIENTO_PLAZO, // Critical type
        'titulo' => 'Evidencia próxima a vencer',
        'mensaje' => 'Tu evidencia vence en 24 horas',
        'enlace' => '/evidencias/123',
        'metadatos' => ['dias_restantes' => 1],
    ]);
    
    // Assert
    expect($notification)->toBeInstanceOf(Notification::class);
    expect($notification->canal)->toBe(Notification::CANAL_AMBOS); // Should include email
    expect($notification->estado_email)->toBe(Notification::EMAIL_ENVIADO);
    
    // Verify email would be sent (mocked)
    // Note: In the actual service, this would trigger Mail::to()->send()
    // but we're mocking it to prevent real email sending
});

it('can force email notification for non-critical events', function () {
    // Act - Force email for non-critical notification
    $notification = NotificationService::create([
        'usuario_id' => $this->user->usuario_id,
        'tipo_evento' => Notification::TIPO_COMENTARIO_NUEVO, // Non-critical
        'titulo' => 'Nuevo comentario',
        'mensaje' => 'Alguien comentó en tu evidencia',
        'forzar_email' => true, // Force email
    ]);
    
    // Assert
    expect($notification->canal)->toBe(Notification::CANAL_AMBOS);
    expect($notification->estado_email)->toBe(Notification::EMAIL_ENVIADO);
});

it('prevents duplicate notifications within 5 minutes', function () {
    // Arrange - Create initial notification
    $firstNotification = NotificationService::create([
        'usuario_id' => $this->user->usuario_id,
        'tipo_evento' => Notification::TIPO_ASIGNACION_EVIDENCIA,
        'titulo' => 'Evidencia asignada',
        'mensaje' => 'Nueva evidencia asignada',
    ]);
    
    // Act - Try to create duplicate notification immediately
    $duplicateNotification = NotificationService::create([
        'usuario_id' => $this->user->usuario_id,
        'tipo_evento' => Notification::TIPO_ASIGNACION_EVIDENCIA,
        'titulo' => 'Evidencia asignada',
        'mensaje' => 'Nueva evidencia asignada',
    ]);
    
    // Assert - Should return the existing notification
    expect($duplicateNotification->notificacion_id)->toBe($firstNotification->notificacion_id);
    
    // Verify only one notification exists in database
    expect(Notification::where('usuario_id', $this->user->usuario_id)
        ->where('tipo_evento', Notification::TIPO_ASIGNACION_EVIDENCIA)
        ->count())->toBe(1);
});

it('can list user notifications with filters', function () {
    // Arrange - Create various notifications
    Notification::factory()->create([
        'usuario_id' => $this->user->usuario_id,
        'tipo_evento' => Notification::TIPO_ASIGNACION_EVIDENCIA,
        'titulo' => 'Evidencia asignada',
        'leida' => false,
    ]);
    
    Notification::factory()->create([
        'usuario_id' => $this->user->usuario_id,
        'tipo_evento' => Notification::TIPO_COMENTARIO_NUEVO,
        'titulo' => 'Nuevo comentario',
        'leida' => true,
        'fecha_lectura' => now(),
    ]);
    
    Notification::factory()->create([
        'usuario_id' => $this->user->usuario_id,
        'tipo_evento' => Notification::TIPO_VENCIMIENTO_PLAZO,
        'titulo' => 'Vencimiento próximo',
        'leida' => false,
    ]);
    
    // Act - Get unread notifications
    $response = $this->getJson('/api/notificaciones?leida=false');
    
    // Assert
    $response->assertStatus(200)
        ->assertJsonStructure([
            'message',
            'data' => [
                '*' => [
                    'notificacion_id',
                    'tipo_evento',
                    'titulo',
                    'mensaje',
                    'leida',
                    'icono',
                    'color',
                    'es_critica',
                ],
            ],
            'total',
        ]);
        
    expect($response->json('total'))->toBe(2); // Only unread notifications
    collect($response->json('data'))->each(function ($item) {
        expect($item['leida'])->toBeFalse();
    });
});

it('can filter notifications by event type', function () {
    // Arrange
    Notification::factory()->create([
        'usuario_id' => $this->user->usuario_id,
        'tipo_evento' => Notification::TIPO_ASIGNACION_EVIDENCIA,
    ]);
    
    Notification::factory()->create([
        'usuario_id' => $this->user->usuario_id,
        'tipo_evento' => Notification::TIPO_COMENTARIO_NUEVO,
    ]);
    
    // Act
    $response = $this->getJson('/api/notificaciones?tipo_evento=' . Notification::TIPO_ASIGNACION_EVIDENCIA);
    
    // Assert
    $response->assertStatus(200);
    expect($response->json('total'))->toBe(1);
    expect($response->json('data.0.tipo_evento'))->toBe(Notification::TIPO_ASIGNACION_EVIDENCIA);
});

it('can get unread notifications count', function () {
    // Arrange
    Notification::factory()->count(3)->create([
        'usuario_id' => $this->user->usuario_id,
        'leida' => false,
    ]);
    
    Notification::factory()->count(2)->create([
        'usuario_id' => $this->user->usuario_id,
        'leida' => true,
    ]);
    
    // Act
    $response = $this->getJson('/api/notificaciones/no-leidas/contador');
    
    // Assert
    $response->assertStatus(200)
        ->assertJson([
            'data' => [
                'contador_no_leidas' => 3,
            ],
        ]);
});

it('can mark notification as read', function () {
    // Arrange
    $notification = Notification::factory()->create([
        'usuario_id' => $this->user->usuario_id,
        'leida' => false,
        'fecha_lectura' => null,
    ]);
    
    // Act
    $response = $this->postJson("/api/notificaciones/{$notification->notificacion_id}/marcar-leida");
    
    // Assert
    $response->assertStatus(200)
        ->assertJson([
            'message' => 'Notificación marcada como leída',
        ]);
    
    // Verify notification was marked as read
    $notification->refresh();
    expect($notification->leida)->toBeTrue();
    expect($notification->fecha_lectura)->not->toBeNull();
});

it('can mark all notifications as read', function () {
    // Arrange
    Notification::factory()->count(3)->create([
        'usuario_id' => $this->user->usuario_id,
        'leida' => false,
    ]);
    
    // Act
    $response = $this->postJson('/api/notificaciones/marcar-todas-leidas');
    
    // Assert
    $response->assertStatus(200);
    expect(str_contains($response->json('message'), 'Se marcaron'))->toBeTrue();
    
    // Verify all notifications are marked as read
    $unreadCount = Notification::where('usuario_id', $this->user->usuario_id)
        ->where('leida', false)
        ->count();
    expect($unreadCount)->toBe(0);
});

it('can delete notification', function () {
    // Arrange
    $notification = Notification::factory()->create([
        'usuario_id' => $this->user->usuario_id,
    ]);
    
    // Act
    $response = $this->deleteJson("/api/notificaciones/{$notification->notificacion_id}");
    
    // Assert
    $response->assertStatus(200)
        ->assertJson([
            'message' => 'Notificación eliminada exitosamente',
        ]);
    
    // Verify notification was deleted
    $this->assertDatabaseMissing('NOTIFICACION', [
        'notificacion_id' => $notification->notificacion_id,
    ]);
});

it('cannot access notifications of other users', function () {
    // Arrange
    $otherUser = User::factory()->create();
    $otherNotification = Notification::factory()->create([
        'usuario_id' => $otherUser->usuario_id,
    ]);
    
    // Act - Try to mark other user's notification as read
    $response = $this->postJson("/api/notificaciones/{$otherNotification->notificacion_id}/marcar-leida");
    
    // Assert
    $response->assertStatus(404); // Should not find notification for current user
    
    // Act - Try to delete other user's notification
    $response = $this->deleteJson("/api/notificaciones/{$otherNotification->notificacion_id}");
    
    // Assert
    $response->assertStatus(404);
});

it('validates notification creation data', function () {
    // Test missing required fields
    expect(function () {
        NotificationService::create([
            'tipo_evento' => Notification::TIPO_ASIGNACION_EVIDENCIA,
            'titulo' => 'Test',
            'mensaje' => 'Test message',
            // Missing usuario_id
        ]);
    })->toThrow(InvalidArgumentException::class);
    
    expect(function () {
        NotificationService::create([
            'usuario_id' => $this->user->usuario_id,
            'titulo' => 'Test',
            'mensaje' => 'Test message',
            // Missing tipo_evento
        ]);
    })->toThrow(InvalidArgumentException::class);
});

it('handles notification with related model correctly', function () {
    // Act
    $notification = NotificationService::create([
        'usuario_id' => $this->user->usuario_id,
        'tipo_evento' => Notification::TIPO_ASIGNACION_EVIDENCIA,
        'titulo' => 'Evidencia asignada',
        'mensaje' => 'Nueva evidencia disponible',
        'relacionado' => $this->evidence,
    ]);
    
    // Assert
    expect($notification->relacionado)->toBeInstanceOf(Evidence::class);
    expect($notification->relacionado->evidencia_id)->toBe($this->evidence->evidencia_id);
    expect($notification->relacionado_type)->toBe(Evidence::class);
    expect($notification->relacionado_id)->toBe($this->evidence->evidencia_id);
});

it('determines notification channel based on event criticality', function () {
    // Test critical notification (should use both channels)
    $criticalNotification = NotificationService::create([
        'usuario_id' => $this->user->usuario_id,
        'tipo_evento' => Notification::TIPO_VENCIMIENTO_PLAZO, // Critical
        'titulo' => 'Vencimiento crítico',
        'mensaje' => 'Action required immediately',
    ]);
    
    expect($criticalNotification->canal)->toBe(Notification::CANAL_AMBOS);
    
    // Test non-critical notification (should use internal only)
    $normalNotification = NotificationService::create([
        'usuario_id' => $this->user->usuario_id,
        'tipo_evento' => Notification::TIPO_COMENTARIO_NUEVO, // Non-critical
        'titulo' => 'Nuevo comentario',
        'mensaje' => 'Someone commented',
    ]);
    
    expect($normalNotification->canal)->toBe(Notification::CANAL_INTERNO);
});

it('prevents sending emails in test environment', function () {
    // This test verifies that Mail::fake() is working properly
    // and no real emails are sent during tests
    
    // Act - Create notification that would normally send email
    NotificationService::create([
        'usuario_id' => $this->user->usuario_id,
        'tipo_evento' => Notification::TIPO_VENCIMIENTO_PLAZO,
        'titulo' => 'Critical notification',
        'mensaje' => 'This should trigger email',
    ]);
    
    // Assert - Email was attempted but no real email is sent
    Mail::assertSent(\App\Mail\TestNotificationMail::class);
    
    // If emails were to be sent, we could test with:
    // Mail::assertSent(SomeNotificationMail::class);
});

it('includes notification metadata in API response', function () {
    // Arrange
    $notification = Notification::factory()->create([
        'usuario_id' => $this->user->usuario_id,
        'metadatos' => [
            'priority' => 'high',
            'source' => 'automated_system',
            'deadline' => '2024-12-31',
        ],
    ]);
    
    // Act
    $response = $this->getJson('/api/notificaciones');
    
    // Assert
    $response->assertStatus(200);
    $notificationData = collect($response->json('data'))
        ->firstWhere('notificacion_id', $notification->notificacion_id);

    expect($notificationData['metadatos'])->toMatchArray([
        'priority' => 'high',
        'source' => 'automated_system',
        'deadline' => '2024-12-31',
    ]);
});

it('requires authentication for notification endpoints', function () {
    // Remove authentication
    $this->app['auth']->forgetGuards();
    
    // Test all endpoints require authentication
    $endpoints = [
        ['GET', '/api/notificaciones'],
        ['GET', '/api/notificaciones/no-leidas/contador'],
        ['POST', '/api/notificaciones/1/marcar-leida'],
        ['POST', '/api/notificaciones/marcar-todas-leidas'],
        ['DELETE', '/api/notificaciones/1'],
    ];
    
    foreach ($endpoints as [$method, $endpoint]) {
        $response = $this->json($method, $endpoint);
        $response->assertStatus(401);
    }
});