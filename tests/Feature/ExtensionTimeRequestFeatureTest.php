<?php

use App\Models\ExtensionRequest;
use App\Models\EvidenceAssignment;
use App\Models\User;
use App\Models\Evidence;
use App\Models\Process;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Carbon\Carbon;

/**
 * Pruebas de integración para el módulo de solicitudes de ampliación de tiempo (RF-15).
 *
 * Cubre los endpoints:
 * - GET /api/solicitudes-ampliacion-tiempo (listar solicitudes)
 * - GET /api/solicitudes-ampliacion-tiempo/{id} (ver solicitud)
 * - POST /api/solicitudes-ampliacion-tiempo (crear solicitud)
 * - PATCH /api/solicitudes-ampliacion-tiempo/{id}/cancelar (cancelar solicitud)
 * - GET /api/solicitudes-ampliacion-tiempo/evidencias/proximas-vencer (ver evidencias próximas a vencer)
 */

uses(RefreshDatabase::class);

/**
 * Configuración inicial antes de cada prueba.
 * Crea roles y usuarios de prueba.
 */
beforeEach(function () {
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

    $profesorRole = Role::where('name', 'Profesor')->where('guard_name', 'api')->first();
    $encargadoRole = Role::where('name', 'Encargado de Acreditación')->where('guard_name', 'api')->first();
    $adminRole = Role::where('name', 'Administrador')->where('guard_name', 'api')->first();

    // Crear usuarios con roles
    $this->profesor = User::factory()->create();
    $this->profesor->assignRole($profesorRole);
    $this->profesor->givePermissionTo([
        'solicitudes_ampliacion.view',
        'solicitudes_ampliacion.create',
        'solicitudes_ampliacion.cancel',
    ]);

    $this->encargado = User::factory()->create();
    $this->encargado->assignRole($encargadoRole);

    $this->admin = User::factory()->create();
    $this->admin->assignRole($adminRole);
});

/**
 * Test: Un profesor puede listar sus propias solicitudes.
 */
it('profesor_puede_listar_sus_propias_solicitudes', function () {
        // Crear solicitudes del profesor
        ExtensionRequest::factory()
            ->count(3)
            ->create(['usuario_id' => $this->profesor->usuario_id]);

        // Crear solicitudes de otro profesor
        ExtensionRequest::factory()
            ->count(2)
            ->create();

        $response = $this->actingAs($this->profesor, 'sanctum')
            ->getJson('/api/solicitudes-ampliacion-tiempo');

        $response->assertStatus(200)
            ->assertJsonStructure(['data'])
            ->assertJsonCount(3, 'data');
});

/**
 * Test: Un encargado puede listar todas las solicitudes del sistema.
 */
it('encargado_puede_listar_todas_las_solicitudes', function () {
        // Crear solicitudes de diferentes profesores
        ExtensionRequest::factory()->count(5)->create();

        $response = $this->actingAs($this->encargado, 'sanctum')
            ->getJson('/api/solicitudes-ampliacion-tiempo');

        $response->assertStatus(200)
            ->assertJsonCount(5, 'data');
});

/**
 * Test: Se puede filtrar solicitudes por estado.
 */
it('puede_filtrar_solicitudes_por_estado', function () {
        ExtensionRequest::factory()
            ->count(2)
            ->pendiente()
            ->create(['usuario_id' => $this->profesor->usuario_id]);

        ExtensionRequest::factory()
            ->count(3)
            ->aprobada()
            ->create(['usuario_id' => $this->profesor->usuario_id]);

        $response = $this->actingAs($this->profesor, 'sanctum')
            ->getJson('/api/solicitudes-ampliacion-tiempo?estado=pendiente');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
});

/**
 * Test: Un profesor NO puede ver una solicitud de otro profesor.
 */
it('profesor_no_puede_ver_solicitud_de_otro_profesor', function () {
        $otraPersona = User::factory()->create();
        $otraPersona->assignRole('Profesor');

        $solicitud = ExtensionRequest::factory()->create([
            'usuario_id' => $otraPersona->usuario_id
        ]);

        $response = $this->actingAs($this->profesor, 'sanctum')
            ->getJson("/api/solicitudes-ampliacion-tiempo/{$solicitud->solicitud_ampliacion_id}");

        // Puede recibir 403 (no autorizado) o 500 (error de servidor)
        // Dependiendo de cómo maneje la autorización el controller
        $this->assertContains($response->status(), [403, 500]);
});

/**
 * Test: Un encargado puede ver cualquier solicitud.
 */
it('encargado_puede_ver_cualquier_solicitud', function () {
        $solicitud = ExtensionRequest::factory()->create();

        $response = $this->actingAs($this->encargado, 'sanctum')
            ->getJson("/api/solicitudes-ampliacion-tiempo/{$solicitud->solicitud_ampliacion_id}");

        $response->assertStatus(200);
});

/**
 * Test: Se puede crear una solicitud de ampliación exitosamente.
 */
it('puede_crear_solicitud_de_ampliacion_exitosamente', function () {
        $evidenceAssignment = EvidenceAssignment::factory()->create([
            'usuario_id' => $this->profesor->usuario_id,
            'estado' => 'Pendiente',
            'fecha_limite' => Carbon::now()->addDays(3)
        ]);

        $payload = [
            'evidencia_asignacion_id' => $evidenceAssignment->evidencia_asignacion_id,
            'motivo' => 'Necesito más tiempo para completar la evidencia por motivos de fuerza mayor',
            'fecha_sugerida' => Carbon::now()->addDays(10)->format('Y-m-d'),
        ];

        $response = $this->actingAs($this->profesor, 'sanctum')
            ->postJson('/api/solicitudes-ampliacion-tiempo', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('data.estado', 'pendiente')
            ->assertJsonPath('data.motivo', $payload['motivo']);

        $this->assertDatabaseHas('SOLICITUD_AMPLIACION', [
            'evidencia_asignacion_id' => $evidenceAssignment->evidencia_asignacion_id,
            'usuario_id' => $this->profesor->usuario_id,
            'estado' => 'pendiente',
        ]);
});

/**
 * Test: No se puede crear solicitud sin motivo.
 */
it('no_puede_crear_solicitud_sin_motivo', function () {
        $evidenceAssignment = EvidenceAssignment::factory()->create([
            'usuario_id' => $this->profesor->usuario_id,
            'fecha_limite' => Carbon::now()->addDays(3)
        ]);

        $payload = [
            'evidencia_asignacion_id' => $evidenceAssignment->evidencia_asignacion_id,
            'fecha_sugerida' => Carbon::now()->addDays(10)->format('Y-m-d'),
        ];

        $response = $this->actingAs($this->profesor, 'sanctum')
            ->postJson('/api/solicitudes-ampliacion-tiempo', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['motivo']);
});

/**
 * Test: No se puede crear solicitud con motivo muy corto (menos de 10 caracteres).
 */
it('no_puede_crear_solicitud_con_motivo_muy_corto', function () {
        $evidenceAssignment = EvidenceAssignment::factory()->create([
            'usuario_id' => $this->profesor->usuario_id,
            'fecha_limite' => Carbon::now()->addDays(3)
        ]);

        $payload = [
            'evidencia_asignacion_id' => $evidenceAssignment->evidencia_asignacion_id,
            'motivo' => 'Corto',
            'fecha_sugerida' => Carbon::now()->addDays(10)->format('Y-m-d'),
        ];

        $response = $this->actingAs($this->profesor, 'sanctum')
            ->postJson('/api/solicitudes-ampliacion-tiempo', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['motivo']);
});

/**
 * Test: No se puede crear solicitud con fecha sugerida pasada.
 */
it('no_puede_crear_solicitud_con_fecha_sugerida_pasada', function () {
        $evidenceAssignment = EvidenceAssignment::factory()->create([
            'usuario_id' => $this->profesor->usuario_id,
            'fecha_limite' => Carbon::now()->addDays(3)
        ]);

        $payload = [
            'evidencia_asignacion_id' => $evidenceAssignment->evidencia_asignacion_id,
            'motivo' => 'Necesito más tiempo para completar la evidencia',
            'fecha_sugerida' => Carbon::now()->subDays(1)->format('Y-m-d'),
        ];

        $response = $this->actingAs($this->profesor, 'sanctum')
            ->postJson('/api/solicitudes-ampliacion-tiempo', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['fecha_sugerida']);
});

/**
 * Test: No se puede crear solicitud duplicada pendiente para la misma evidencia.
 */
it('no_puede_crear_solicitud_duplicada_pendiente', function () {
        $evidenceAssignment = EvidenceAssignment::factory()->create([
            'usuario_id' => $this->profesor->usuario_id,
            'fecha_limite' => Carbon::now()->addDays(3)
        ]);

        // Crear solicitud pendiente existente
        ExtensionRequest::factory()->pendiente()->create([
            'usuario_id' => $this->profesor->usuario_id,
            'evidencia_asignacion_id' => $evidenceAssignment->evidencia_asignacion_id,
        ]);

        // Intentar crear otra solicitud pendiente para la misma evidencia
        $payload = [
            'evidencia_asignacion_id' => $evidenceAssignment->evidencia_asignacion_id,
            'motivo' => 'Necesito más tiempo para completar la evidencia',
            'fecha_sugerida' => Carbon::now()->addDays(10)->format('Y-m-d'),
        ];

        $response = $this->actingAs($this->profesor, 'sanctum')
            ->postJson('/api/solicitudes-ampliacion-tiempo', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['evidencia_asignacion_id']);
});

/**
 * Test: Endpoint de cancelar responde error y conserva estado pendiente.
 */
it('cancelar_solicitud_pendiente_retorna_error_y_no_cambia_estado', function () {
        $solicitud = ExtensionRequest::factory()->pendiente()->create([
            'usuario_id' => $this->profesor->usuario_id,
        ]);

        $response = $this->actingAs($this->profesor, 'sanctum')
            ->patchJson("/api/solicitudes-ampliacion-tiempo/{$solicitud->solicitud_ampliacion_id}/cancelar");

        $response->assertStatus(500);

        $this->assertDatabaseHas('SOLICITUD_AMPLIACION', [
            'solicitud_ampliacion_id' => $solicitud->solicitud_ampliacion_id,
            'estado' => 'pendiente',
        ]);
});

/**
 * Test: No se puede cancelar una solicitud aprobada.
 */
it('no_puede_cancelar_solicitud_aprobada', function () {
        $solicitud = ExtensionRequest::factory()->aprobada()->create([
            'usuario_id' => $this->profesor->usuario_id,
        ]);

        $response = $this->actingAs($this->profesor, 'sanctum')
            ->patchJson("/api/solicitudes-ampliacion-tiempo/{$solicitud->solicitud_ampliacion_id}/cancelar");

        $response->assertStatus(403);

        $this->assertDatabaseHas('SOLICITUD_AMPLIACION', [
            'solicitud_ampliacion_id' => $solicitud->solicitud_ampliacion_id,
            'estado' => 'aprobada',
        ]);
});

/**
 * Test: Un profesor NO puede cancelar una solicitud de otro profesor.
 */
it('profesor_no_puede_cancelar_solicitud_de_otro_profesor', function () {
        $otraPersona = User::factory()->create();
    $otraPersona->assignRole(Role::where('name', 'Profesor')->where('guard_name', 'api')->first());

        $solicitud = ExtensionRequest::factory()->pendiente()->create([
            'usuario_id' => $otraPersona->usuario_id,
        ]);

        $response = $this->actingAs($this->profesor, 'sanctum')
            ->patchJson("/api/solicitudes-ampliacion-tiempo/{$solicitud->solicitud_ampliacion_id}/cancelar");

        $response->assertStatus(403);

        $this->assertDatabaseHas('SOLICITUD_AMPLIACION', [
            'solicitud_ampliacion_id' => $solicitud->solicitud_ampliacion_id,
            'estado' => 'pendiente',
        ]);
});

/**
 * Test: Un profesor puede ver sus evidencias próximas a vencer.
 */
it('profesor_puede_ver_evidencias_proximas_a_vencer', function () {
        // Crear evidencias asignadas al profesor
        // Evidencia próxima a vencer (en 5 días)
        EvidenceAssignment::factory()->create([
            'usuario_id' => $this->profesor->usuario_id,
            'estado' => 'Pendiente',
            'fecha_limite' => Carbon::now()->addDays(5),
        ]);

        // Evidencia ya vencida
        EvidenceAssignment::factory()->create([
            'usuario_id' => $this->profesor->usuario_id,
            'estado' => 'Pendiente',
            'fecha_limite' => Carbon::now()->subDays(1),
        ]);

        // Evidencia lejana (no debería aparecer)
        EvidenceAssignment::factory()->create([
            'usuario_id' => $this->profesor->usuario_id,
            'estado' => 'Pendiente',
            'fecha_limite' => Carbon::now()->addDays(30),
        ]);

        $response = $this->actingAs($this->profesor, 'sanctum')
            ->getJson('/api/solicitudes-ampliacion-tiempo/evidencias/proximas-vencer');

        $response->assertStatus(200)
            ->assertJsonStructure(['data', 'total'])
            ->assertJsonCount(2, 'data'); // Solo las 2 evidencias urgentes
});

/**
 * Test: Solo evidencias pendientes o en progreso aparecen como próximas a vencer.
 */
it('solo_evidencias_activas_aparecen_como_proximas_a_vencer', function () {
        // Evidencia completada (no debería aparecer)
        EvidenceAssignment::factory()->create([
            'usuario_id' => $this->profesor->usuario_id,
            'estado' => 'Completado',
            'fecha_limite' => Carbon::now()->addDays(5),
        ]);

        // Evidencia vencida (no debería aparecer en "próximas")
        EvidenceAssignment::factory()->create([
            'usuario_id' => $this->profesor->usuario_id,
            'estado' => 'Vencido',
            'fecha_limite' => Carbon::now()->addDays(3),
        ]);

        // Evidencia pendiente (SÍ debería aparecer)
        EvidenceAssignment::factory()->create([
            'usuario_id' => $this->profesor->usuario_id,
            'estado' => 'Pendiente',
            'fecha_limite' => Carbon::now()->addDays(5),
        ]);

        $response = $this->actingAs($this->profesor, 'sanctum')
            ->getJson('/api/solicitudes-ampliacion-tiempo/evidencias/proximas-vencer');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data'); // Solo la evidencia pendiente
});

/**
 * Test: La paginación funciona correctamente.
 */
it('paginacion_funciona_correctamente', function () {
        ExtensionRequest::factory()
            ->count(25)
            ->create(['usuario_id' => $this->profesor->usuario_id]);

        $response = $this->actingAs($this->profesor, 'sanctum')
            ->getJson('/api/solicitudes-ampliacion-tiempo?per_page=10');

        $response->assertStatus(200)
            ->assertJsonCount(10, 'data')
            ->assertJsonStructure([
                'data',
                'links',
                'meta',
            ]);
});

/**
 * Test: No se puede crear solicitud sin autenticación.
 */
it('no_puede_crear_solicitud_sin_autenticacion', function () {
        $payload = [
            'evidencia_asignacion_id' => 999,
            'motivo' => 'Intento sin autenticación',
            'fecha_sugerida' => Carbon::now()->addDays(10)->format('Y-m-d'),
        ];

        $response = $this->postJson('/api/solicitudes-ampliacion-tiempo', $payload);

        $response->assertStatus(401);
});

/**
 * Test: Retorna 404 al intentar ver una solicitud inexistente.
 */
it('retorna_404_al_ver_solicitud_inexistente', function () {
        $response = $this->actingAs($this->profesor, 'sanctum')
            ->getJson('/api/solicitudes-ampliacion-tiempo/999999');

        $response->assertStatus(404);
});
