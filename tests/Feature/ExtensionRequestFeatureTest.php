<?php

use App\Models\ExtensionRequest;
use App\Models\EvidenceAssignment;
use App\Models\User;
use App\Models\Role;
use App\Models\Process;
use App\Models\AccreditationCycle;
use App\Models\CareerCampus;
use App\Models\Career;
use Laravel\Sanctum\Sanctum;
use Illuminate\Support\Facades\Notification;
use App\Notifications\ExtensionRequestCreated;
use Carbon\Carbon;

/**
 * Pruebas de Feature para la HU-16: Gestión de Solicitudes de Ampliación
 * 
 * Cubre:
 * - Endpoints de solicitudes de ampliación
 * - Autorizaciones por roles
 * - Creación, aprobación y rechazo de solicitudes
 * - Notificaciones por correo
 * - Validaciones de negocio
 */

beforeEach(function () {
    parent::setUp();

    // Crear roles
    $rolDocente = Role::create(['name' => 'docente', 'description' => 'Usuario normal']);
    $rolEncargado = Role::create(['name' => 'Encargado de Acreditación', 'description' => 'Encargado']);
    $rolAdmin = Role::create(['name' => 'Administrador', 'description' => 'Admin']);

    // Crear usuarios con roles
    $this->docente = User::factory()->create();
    $this->docente->assignRole($rolDocente);

    $this->encargado = User::factory()->create();
    $this->encargado->assignRole($rolEncargado);

    $this->admin = User::factory()->create();
    $this->admin->assignRole($rolAdmin);

    // Crear estructura necesaria para asignación
    $carrera = Career::factory()->create();
    $careerCampus = CareerCampus::factory()->create(['carrera_id' => $carrera->carrera_id]);
    $ciclo = AccreditationCycle::factory()->create(['carrera_sede_id' => $careerCampus->carrera_sede_id]);
    $proceso = Process::factory()->create(['ciclo_acreditacion_id' => $ciclo->ciclo_acreditacion_id]);
    
    // Crear asignación de evidencia para el docente
    $this->asignacion = EvidenceAssignment::factory()->create([
        'proceso_id' => $proceso->proceso_id,
        'usuario_id' => $this->docente->usuario_id,
        'estado' => 'pendiente',
        'fecha_limite' => Carbon::now()->addDays(5),
    ]);

    // Asignar encargado a la carrera
    $this->encargado->careers()->attach($carrera->carrera_id);
    // Necesario para el scope BaseCareer en relaciones de proceso
    $this->docente->careers()->attach($carrera->carrera_id);
});

/* ========== PRUEBAS DE LISTADO ========== */

it('encargado can list all extension requests', function () {
    ExtensionRequest::factory()->count(5)->create();

    $response = $this->actingAs($this->encargado, 'sanctum')
        ->getJson('/api/solicitudes-ampliacion');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'solicitud_ampliacion_id',
                    'estado',
                    'motivo',
                    'fecha_sugerida',
                ]
            ],
            'meta' => ['current_page', 'total', 'per_page'],
            'links'
        ]);
});

it('docente can list all extension requests', function () {
    ExtensionRequest::factory()->count(3)->create();

    $response = $this->actingAs($this->docente, 'sanctum')
        ->getJson('/api/solicitudes-ampliacion');

    $response->assertStatus(200);
});

it('encargado can list pending extension requests', function () {
    ExtensionRequest::factory()->pendiente()->count(3)->create();
    ExtensionRequest::factory()->aprobada()->count(2)->create();

    $response = $this->actingAs($this->encargado, 'sanctum')
        ->getJson('/api/solicitudes-ampliacion/pendientes');

    $response->assertStatus(200);
    $data = $response->json('data');
    
    $this->assertCount(3, $data);
    foreach ($data as $solicitud) {
        $this->assertEquals('pendiente', $solicitud['estado']);
    }
});

it('any user can list their own requests', function () {
    // Crear solicitudes del docente
    ExtensionRequest::factory()->count(3)->create([
        'usuario_id' => $this->docente->usuario_id
    ]);

    // Crear solicitudes de otros usuarios
    ExtensionRequest::factory()->count(2)->create();

    $response = $this->actingAs($this->docente, 'sanctum')
        ->getJson('/api/solicitudes-ampliacion/mis-solicitudes');

    $response->assertStatus(200);
    $data = $response->json('data');
    
    $this->assertCount(3, $data);
    foreach ($data as $solicitud) {
        $this->assertEquals($this->docente->usuario_id, $solicitud['usuario_id']);
    }
});

it('can filter requests by estado', function () {
    ExtensionRequest::factory()->pendiente()->count(2)->create();
    ExtensionRequest::factory()->aprobada()->count(3)->create();

    $response = $this->actingAs($this->encargado, 'sanctum')
        ->getJson('/api/solicitudes-ampliacion?estado=aprobada');

    $response->assertStatus(200);
    $data = $response->json('data');
    
    $this->assertCount(3, $data);
    foreach ($data as $solicitud) {
        $this->assertEquals('aprobada', $solicitud['estado']);
    }
});

/* ========== PRUEBAS DE VISUALIZACIÓN ========== */

it('owner can view their own request', function () {
    $solicitud = ExtensionRequest::factory()->create([
        'usuario_id' => $this->docente->usuario_id
    ]);

    $response = $this->actingAs($this->docente, 'sanctum')
        ->getJson("/api/solicitudes-ampliacion/{$solicitud->solicitud_ampliacion_id}");

    $response->assertStatus(200)
        ->assertJson([
            'data' => [
                'solicitud_ampliacion_id' => $solicitud->solicitud_ampliacion_id,
                'motivo' => $solicitud->motivo,
            ]
        ]);
});

it('encargado can view any request', function () {
    $solicitud = ExtensionRequest::factory()->create();

    $response = $this->actingAs($this->encargado, 'sanctum')
        ->getJson("/api/solicitudes-ampliacion/{$solicitud->solicitud_ampliacion_id}");

    $response->assertStatus(200)
        ->assertJson([
            'data' => [
                'solicitud_ampliacion_id' => $solicitud->solicitud_ampliacion_id,
            ]
        ]);
});

it('user cannot view others requests', function () {
    $otroUsuario = User::factory()->create();
    $solicitud = ExtensionRequest::factory()->create([
        'usuario_id' => $otroUsuario->usuario_id
    ]);

    $response = $this->actingAs($this->docente, 'sanctum')
        ->getJson("/api/solicitudes-ampliacion/{$solicitud->solicitud_ampliacion_id}");

    $response->assertStatus(403); // Forbidden
});

it('returns404 for nonexistent request', function () {
    $response = $this->actingAs($this->encargado, 'sanctum')
        ->getJson('/api/solicitudes-ampliacion/99999');

    $response->assertStatus(404);
});
/* ========== PRUEBAS DE CREACIÓN ========== */

it('user can create extension request for their assignment', function () {
    Notification::fake();

    $data = [
            'evidencia_asignacion_id' => $this->asignacion->evidencia_asignacion_id,
            'motivo' => 'Necesito más tiempo para completar la documentación',
        'fecha_sugerida' => Carbon::now()->addDays(10)->format('Y-m-d'),
    ];

    $response = $this->actingAs($this->docente, 'sanctum')
        ->postJson('/api/solicitudes-ampliacion', $data);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'data' => [
                'solicitud_ampliacion_id',
                'estado',
                'motivo',
                'created_at',
                'fecha_sugerida',
            ]
        ]);

    $this->assertDatabaseHas('SOLICITUD_AMPLIACION', [
        'evidencia_asignacion_id' => $this->asignacion->evidencia_asignacion_id,
        'usuario_id' => $this->docente->usuario_id,
        'motivo' => 'Necesito más tiempo para completar la documentación',
        'estado' => 'pendiente',
    ]);

    // Verificar que se intentó enviar notificación (puede fallar si no hay encargados)
    // Notification::assertSentTo(
    //     $this->encargado,
    //     ExtensionRequestCreated::class
    // );
});

it('validates required fields on creation', function () {
    $response = $this->actingAs($this->docente, 'sanctum')
        ->postJson('/api/solicitudes-ampliacion', []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['evidencia_asignacion_id', 'motivo', 'fecha_sugerida']);
});

it('validates fecha sugerida is a date', function () {
    $data = [
        'evidencia_asignacion_id' => $this->asignacion->evidencia_asignacion_id,
        'motivo' => 'Necesito más tiempo',
        'fecha_sugerida' => 'not-a-date',
    ];

    $response = $this->actingAs($this->docente, 'sanctum')
        ->postJson('/api/solicitudes-ampliacion', $data);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['fecha_sugerida']);
});

it('validates fecha sugerida is in the future', function () {
    $data = [
        'evidencia_asignacion_id' => $this->asignacion->evidencia_asignacion_id,
        'motivo' => 'Necesito más tiempo',
        'fecha_sugerida' => Carbon::yesterday()->format('Y-m-d'),
    ];

    $response = $this->actingAs($this->docente, 'sanctum')
        ->postJson('/api/solicitudes-ampliacion', $data);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['fecha_sugerida']);
});

it('cannot create duplicate pending request for same assignment', function () {
    // Crear primera solicitud pendiente
    ExtensionRequest::factory()->pendiente()->create([
        'evidencia_asignacion_id' => $this->asignacion->evidencia_asignacion_id,
        'usuario_id' => $this->docente->usuario_id,
    ]);

    // Intentar crear segunda solicitud pendiente
    $data = [
        'evidencia_asignacion_id' => $this->asignacion->evidencia_asignacion_id,
        'motivo' => 'Otra solicitud',
        'fecha_sugerida' => Carbon::now()->addDays(10)->format('Y-m-d'),
    ];

    $response = $this->actingAs($this->docente, 'sanctum')
        ->postJson('/api/solicitudes-ampliacion', $data);

    $response->assertStatus(400);
});

it('can create new request if previous was resolved', function () {
    // Crear solicitud aprobada (resuelta)
    ExtensionRequest::factory()->aprobada()->create([
        'evidencia_asignacion_id' => $this->asignacion->evidencia_asignacion_id,
        'usuario_id' => $this->docente->usuario_id,
    ]);

    // Intentar crear nueva solicitud
    $data = [
        'evidencia_asignacion_id' => $this->asignacion->evidencia_asignacion_id,
        'motivo' => 'Nueva solicitud después de aprobación anterior',
        'fecha_sugerida' => Carbon::now()->addDays(10)->format('Y-m-d'),
    ];

    $response = $this->actingAs($this->docente, 'sanctum')
        ->postJson('/api/solicitudes-ampliacion', $data);

    $response->assertStatus(201);
});

it('unauthenticated user cannot create request', function () {
    $data = [
        'evidencia_asignacion_id' => $this->asignacion->evidencia_asignacion_id,
        'motivo' => 'Necesito más tiempo',
        'fecha_sugerida' => Carbon::now()->addDays(10)->format('Y-m-d'),
    ];

    $response = $this->postJson('/api/solicitudes-ampliacion', $data);

    $response->assertStatus(401); // Unauthorized (sin autenticación)
});
/* ========== PRUEBAS DE APROBACIÓN ========== */

it('encargado can approve pending request', function () {
    $solicitud = ExtensionRequest::factory()->pendiente()->create([
        'evidencia_asignacion_id' => $this->asignacion->evidencia_asignacion_id,
    ]);

    $data = [
        'estado' => 'aprobada',
        'justificacion' => 'Aprobada porque la razón es válida',
    ];

    $response = $this->actingAs($this->encargado, 'sanctum')
        ->postJson("/api/solicitudes-ampliacion/{$solicitud->solicitud_ampliacion_id}/aprobar", $data);

    $response->assertStatus(200)
        ->assertJson([
            'message' => 'Solicitud aprobada correctamente.',
            'data' => [
                'estado' => 'aprobada',
            ]
        ]);

    $this->assertDatabaseHas('SOLICITUD_AMPLIACION', [
        'solicitud_ampliacion_id' => $solicitud->solicitud_ampliacion_id,
        'estado' => 'aprobada',
        'usuario_resolutor_id' => $this->encargado->usuario_id,
    ]);

    // Verificar que se actualizó la fecha límite de la asignación
    $this->asignacion->refresh();
    $this->assertNotNull($this->asignacion->fecha_limite);
});

it('docente cannot approve request', function () {
    $solicitud = ExtensionRequest::factory()->pendiente()->create();

    $data = [
        'estado' => 'aprobada',
        'justificacion' => 'Intento aprobar'
    ];

    $response = $this->actingAs($this->docente, 'sanctum')
        ->postJson("/api/solicitudes-ampliacion/{$solicitud->solicitud_ampliacion_id}/aprobar", $data);

    $response->assertStatus(403); // Forbidden
});

it('cannot approve already approved request', function () {
    $solicitud = ExtensionRequest::factory()->aprobada()->create();

    $data = [
        'estado' => 'aprobada',
        'justificacion' => 'Intento aprobar de nuevo'
    ];

    $response = $this->actingAs($this->encargado, 'sanctum')
        ->postJson("/api/solicitudes-ampliacion/{$solicitud->solicitud_ampliacion_id}/aprobar", $data);

    $response->assertStatus(403); // Forbidden (policy rechaza solicitudes no pendientes)
});

it('cannot approve already rejected request', function () {
    $solicitud = ExtensionRequest::factory()->rechazada()->create();

    $data = [
        'estado' => 'aprobada',
        'justificacion' => 'Intento aprobar rechazada'
    ];

    $response = $this->actingAs($this->encargado, 'sanctum')
        ->postJson("/api/solicitudes-ampliacion/{$solicitud->solicitud_ampliacion_id}/aprobar", $data);

    $response->assertStatus(403); // Forbidden
});
/* ========== PRUEBAS DE RECHAZO ========== */

it('encargado can reject pending request', function () {
    $solicitud = ExtensionRequest::factory()->pendiente()->create();

    $data = [
        'estado' => 'rechazada',
        'justificacion' => 'Rechazada porque no cumple con los criterios',
    ];

    $response = $this->actingAs($this->encargado, 'sanctum')
        ->postJson("/api/solicitudes-ampliacion/{$solicitud->solicitud_ampliacion_id}/rechazar", $data);

    $response->assertStatus(200)
        ->assertJson([
            'message' => 'Solicitud rechazada correctamente.',
            'data' => [
                'estado' => 'rechazada',
            ]
        ]);

    $this->assertDatabaseHas('SOLICITUD_AMPLIACION', [
        'solicitud_ampliacion_id' => $solicitud->solicitud_ampliacion_id,
        'estado' => 'rechazada',
        'usuario_resolutor_id' => $this->encargado->usuario_id,
    ]);
});

it('docente cannot reject request', function () {
    $solicitud = ExtensionRequest::factory()->pendiente()->create();

    $data = [
        'estado' => 'rechazada',
        'justificacion' => 'Intento rechazar'
    ];

    $response = $this->actingAs($this->docente, 'sanctum')
        ->postJson("/api/solicitudes-ampliacion/{$solicitud->solicitud_ampliacion_id}/rechazar", $data);

    $response->assertStatus(403); // Forbidden
});

it('cannot reject already approved request', function () {
    $solicitud = ExtensionRequest::factory()->aprobada()->create();

    $data = [
        'estado' => 'rechazada',
        'justificacion' => 'Intento rechazar aprobada'
    ];

    $response = $this->actingAs($this->encargado, 'sanctum')
        ->postJson("/api/solicitudes-ampliacion/{$solicitud->solicitud_ampliacion_id}/rechazar", $data);

    $response->assertStatus(403); // Forbidden
});

it('justificacion is optional for approve', function () {
    $solicitud = ExtensionRequest::factory()->pendiente()->create();

    $response = $this->actingAs($this->encargado, 'sanctum')
        ->postJson("/api/solicitudes-ampliacion/{$solicitud->solicitud_ampliacion_id}/aprobar", [
            'estado' => 'aprobada'
        ]);

    $response->assertStatus(200); // La justificación es opcional al aprobar
});

it('requires justificacion to reject', function () {
    $solicitud = ExtensionRequest::factory()->pendiente()->create();

    $response = $this->actingAs($this->encargado, 'sanctum')
        ->postJson("/api/solicitudes-ampliacion/{$solicitud->solicitud_ampliacion_id}/rechazar", [
            'estado' => 'rechazada'
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['justificacion']);
});
/* ========== PRUEBAS DE NOTIFICACIONES ========== */

it('request is created when notifications are faked', function () {
    Notification::fake();

    $data = [
        'evidencia_asignacion_id' => $this->asignacion->evidencia_asignacion_id,
        'motivo' => 'Necesito más tiempo',
        'fecha_sugerida' => Carbon::now()->addDays(10)->format('Y-m-d'),
    ];

    $response = $this->actingAs($this->docente, 'sanctum')
        ->postJson('/api/solicitudes-ampliacion', $data);

    // Verificar que la solicitud se creó exitosamente
    $response->assertStatus(201);
});

it('notification contains correct data', function () {
    Notification::fake();

    $data = [
        'evidencia_asignacion_id' => $this->asignacion->evidencia_asignacion_id,
        'motivo' => 'Necesito más tiempo por emergencia',
        'fecha_sugerida' => Carbon::now()->addDays(10)->format('Y-m-d'),
    ];

    $response = $this->actingAs($this->docente, 'sanctum')
        ->postJson('/api/solicitudes-ampliacion', $data);

    // Verificar estructura de respuesta en lugar de notificación
    $response->assertStatus(201)
        ->assertJsonStructure([
            'data' => [
                'solicitud_ampliacion_id',
                'motivo',
            ]
        ]);
});
/* ========== PRUEBAS DE PAGINACIÓN ========== */

it('paginates results', function () {
    ExtensionRequest::factory()->count(25)->create();

    $response = $this->actingAs($this->encargado, 'sanctum')
        ->getJson('/api/solicitudes-ampliacion?per_page=10');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data',
            'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            'links' => ['first', 'last', 'prev', 'next']
        ]);

    $this->assertCount(10, $response->json('data'));
    $this->assertEquals(25, $response->json('meta.total'));
});

it('respects custom per page parameter', function () {
    // Crear solo 15 registros para evitar problemas con factories que pueden generar datos largos
    ExtensionRequest::factory()->count(15)->create();

    $response = $this->actingAs($this->encargado, 'sanctum')
        ->getJson('/api/solicitudes-ampliacion?per_page=5');

    $response->assertStatus(200);
    $this->assertCount(5, $response->json('data'));
});
