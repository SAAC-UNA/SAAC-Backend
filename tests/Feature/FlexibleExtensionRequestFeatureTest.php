<?php

use App\Models\ExtensionRequest;
use App\Models\ElementAssignment;
use App\Models\StructureElement;
use App\Models\User;
use App\Models\Process;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Notification;
use Carbon\Carbon;

/**
 * Feature Tests — FlexibleExtensionRequestController (HU-016b modelo flexible)
 *
 * Cubre los 7 endpoints bajo /api/elemento-solicitudes-ampliacion:
 *   GET  /                      → index (encargados)
 *   GET  /pendientes            → pending (encargados)
 *   GET  /mis-solicitudes       → mySolicitudes
 *   GET  /{id}                  → show
 *   POST /                      → store
 *   POST /{id}/aprobar          → approve
 *   POST /{id}/rechazar         → reject
 *
 * Verifica también el AISLAMIENTO: las solicitudes tradicionales
 * (evidencia_asignacion_id) no aparecen en estos endpoints.
 */

beforeEach(function () {
    Notification::fake();

    $rolDocente   = Role::firstOrCreate(['name' => 'Profesor',                  'guard_name' => 'api']);
    $rolEncargado = Role::firstOrCreate(['name' => 'Encargado de Acreditacion', 'guard_name' => 'api']);

    // Permisos requeridos por ExtensionRequestPolicy
    $permisos = [
        'solicitudes_ampliacion.view',
        'solicitudes_ampliacion.create',
        'solicitudes_ampliacion.approve',
        'solicitudes_ampliacion.reject',
    ];
    foreach ($permisos as $p) {
        $perm = \Spatie\Permission\Models\Permission::firstOrCreate(['name' => $p, 'guard_name' => 'api']);
        $rolEncargado->givePermissionTo($perm);
    }
    $rolDocente->givePermissionTo(
        \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'solicitudes_ampliacion.view',   'guard_name' => 'api']),
        \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'solicitudes_ampliacion.create', 'guard_name' => 'api'])
    );

    $this->docente   = User::factory()->create();
    $this->encargado = User::factory()->create();

    $this->docente->assignRole($rolDocente);
    $this->encargado->assignRole($rolEncargado);

    // Estructura flexible
    $this->proceso   = Process::factory()->create();
    $this->elemento  = StructureElement::factory()->create();
    $this->asignacion = ElementAssignment::factory()->create([
        'elemento_id' => $this->elemento->elemento_id,
        'usuario_id'  => $this->docente->usuario_id,
        'proceso_id'  => $this->proceso->proceso_id,
        'estado'      => \App\Models\ElementAssignment::ESTADO_PENDIENTE,
        'fecha_limite' => Carbon::now()->addDays(5),
    ]);
});

/* ========== AUTENTICACIÓN ========== */

it('requires authentication for flexible extension request endpoints', function () {
    $this->getJson('/api/elemento-solicitudes-ampliacion')->assertStatus(401);
    $this->postJson('/api/elemento-solicitudes-ampliacion')->assertStatus(401);
});

/* ========== INDEX ========== */

it('encargado can list flexible extension requests', function () {
    ExtensionRequest::factory()->count(3)->create([
        'elemento_asignacion_id'  => $this->asignacion->elemento_asignacion_id,
        'evidencia_asignacion_id' => null,
        'usuario_id'              => $this->docente->usuario_id,
    ]);

    $response = $this->actingAs($this->encargado, 'sanctum')
        ->getJson('/api/elemento-solicitudes-ampliacion');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => ['*' => ['solicitud_ampliacion_id', 'estado', 'motivo']],
            'meta' => ['current_page', 'total', 'per_page'],
            'links',
        ]);

    $this->assertCount(3, $response->json('data'));
});

it('index only shows flexible requests (not tradicional)', function () {
    // Solicitudes flexible
    ExtensionRequest::factory()->count(2)->create([
        'elemento_asignacion_id'  => $this->asignacion->elemento_asignacion_id,
        'evidencia_asignacion_id' => null,
        'usuario_id'              => $this->docente->usuario_id,
    ]);

    // Solicitudes tradicionales (deben quedar fuera)
    ExtensionRequest::factory()->count(4)->create([
        'elemento_asignacion_id'  => null,
        'evidencia_asignacion_id' => \App\Models\EvidenceAssignment::factory()->create()->evidencia_asignacion_id,
        'usuario_id'              => $this->docente->usuario_id,
    ]);

    $response = $this->actingAs($this->encargado, 'sanctum')
        ->getJson('/api/elemento-solicitudes-ampliacion');

    $response->assertStatus(200);
    // Solo las 2 flexibles deben aparecer
    $this->assertCount(2, $response->json('data'));
});

/* ========== PENDING ========== */

it('encargado can list pending flexible requests', function () {
    ExtensionRequest::factory()->count(3)->create([
        'elemento_asignacion_id'  => $this->asignacion->elemento_asignacion_id,
        'evidencia_asignacion_id' => null,
        'estado'                  => ExtensionRequest::ESTADO_PENDIENTE,
        'usuario_id'              => $this->docente->usuario_id,
    ]);
    ExtensionRequest::factory()->count(2)->create([
        'elemento_asignacion_id'  => $this->asignacion->elemento_asignacion_id,
        'evidencia_asignacion_id' => null,
        'estado'                  => ExtensionRequest::ESTADO_APROBADA,
        'usuario_id'              => $this->docente->usuario_id,
    ]);

    $response = $this->actingAs($this->encargado, 'sanctum')
        ->getJson('/api/elemento-solicitudes-ampliacion/pendientes');

    $response->assertStatus(200);
    $this->assertCount(3, $response->json('data'));

    foreach ($response->json('data') as $item) {
        $this->assertEquals(ExtensionRequest::ESTADO_PENDIENTE, $item['estado']);
    }
});

/* ========== MIS SOLICITUDES ========== */

it('docente can list only their own flexible requests', function () {
    ExtensionRequest::factory()->count(3)->create([
        'elemento_asignacion_id'  => $this->asignacion->elemento_asignacion_id,
        'evidencia_asignacion_id' => null,
        'usuario_id'              => $this->docente->usuario_id,
    ]);
    // Solicitudes de otro usuario — no deben aparecer
    ExtensionRequest::factory()->count(5)->create([
        'elemento_asignacion_id'  => $this->asignacion->elemento_asignacion_id,
        'evidencia_asignacion_id' => null,
        'usuario_id'              => User::factory()->create()->usuario_id,
    ]);

    $response = $this->actingAs($this->docente, 'sanctum')
        ->getJson('/api/elemento-solicitudes-ampliacion/mis-solicitudes');

    $response->assertStatus(200);
    $this->assertCount(3, $response->json('data'));
});

/* ========== SHOW ========== */

it('owner can view their own flexible request', function () {
    $solicitud = ExtensionRequest::factory()->create([
        'elemento_asignacion_id'  => $this->asignacion->elemento_asignacion_id,
        'evidencia_asignacion_id' => null,
        'usuario_id'              => $this->docente->usuario_id,
    ]);

    $response = $this->actingAs($this->docente, 'sanctum')
        ->getJson("/api/elemento-solicitudes-ampliacion/{$solicitud->solicitud_ampliacion_id}");

    $response->assertStatus(200)
        ->assertJson(['data' => ['solicitud_ampliacion_id' => $solicitud->solicitud_ampliacion_id]]);
});

it('encargado can view any flexible request', function () {
    $solicitud = ExtensionRequest::factory()->create([
        'elemento_asignacion_id'  => $this->asignacion->elemento_asignacion_id,
        'evidencia_asignacion_id' => null,
        'usuario_id'              => $this->docente->usuario_id,
    ]);

    $response = $this->actingAs($this->encargado, 'sanctum')
        ->getJson("/api/elemento-solicitudes-ampliacion/{$solicitud->solicitud_ampliacion_id}");

    $response->assertStatus(200);
});

it('cannot view another users flexible request', function () {
    $otro = User::factory()->create();
    $solicitud = ExtensionRequest::factory()->create([
        'elemento_asignacion_id'  => $this->asignacion->elemento_asignacion_id,
        'evidencia_asignacion_id' => null,
        'usuario_id'              => $otro->usuario_id,
    ]);

    $response = $this->actingAs($this->docente, 'sanctum')
        ->getJson("/api/elemento-solicitudes-ampliacion/{$solicitud->solicitud_ampliacion_id}");

    $response->assertStatus(403);
});

it('returns 404 for nonexistent flexible request', function () {
    $response = $this->actingAs($this->encargado, 'sanctum')
        ->getJson('/api/elemento-solicitudes-ampliacion/99999');

    $response->assertStatus(404);
});

/* ========== STORE ========== */

it('docente can create a flexible extension request', function () {
    $response = $this->actingAs($this->docente, 'sanctum')
        ->postJson('/api/elemento-solicitudes-ampliacion', [
            'elemento_asignacion_id' => $this->asignacion->elemento_asignacion_id,
            'motivo'                 => 'Necesito más tiempo para reunir la documentación requerida',
            'fecha_sugerida'         => Carbon::now()->addDays(15)->format('Y-m-d'),
        ]);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'data' => ['solicitud_ampliacion_id', 'estado', 'motivo', 'fecha_sugerida'],
        ]);

    $this->assertDatabaseHas('SOLICITUD_AMPLIACION', [
        'elemento_asignacion_id' => $this->asignacion->elemento_asignacion_id,
        'usuario_id'             => $this->docente->usuario_id,
        'estado'                 => ExtensionRequest::ESTADO_PENDIENTE,
    ]);
});

it('validates required fields on flexible store', function () {
    $response = $this->actingAs($this->docente, 'sanctum')
        ->postJson('/api/elemento-solicitudes-ampliacion', []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['elemento_asignacion_id', 'motivo', 'fecha_sugerida']);
});

it('rejects past fecha_sugerida on flexible store', function () {
    $response = $this->actingAs($this->docente, 'sanctum')
        ->postJson('/api/elemento-solicitudes-ampliacion', [
            'elemento_asignacion_id' => $this->asignacion->elemento_asignacion_id,
            'motivo'                 => 'Justificación de prueba con texto suficiente',
            'fecha_sugerida'         => Carbon::yesterday()->format('Y-m-d'),
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['fecha_sugerida']);
});

it('cannot create duplicate pending flexible request for same assignment', function () {
    // Primera solicitud pendiente
    ExtensionRequest::factory()->create([
        'elemento_asignacion_id'  => $this->asignacion->elemento_asignacion_id,
        'evidencia_asignacion_id' => null,
        'usuario_id'              => $this->docente->usuario_id,
        'estado'                  => ExtensionRequest::ESTADO_PENDIENTE,
    ]);

    // Intentar crear una segunda
    $response = $this->actingAs($this->docente, 'sanctum')
        ->postJson('/api/elemento-solicitudes-ampliacion', [
            'elemento_asignacion_id' => $this->asignacion->elemento_asignacion_id,
            'motivo'                 => 'Segunda solicitud para la misma asignación',
            'fecha_sugerida'         => Carbon::now()->addDays(10)->format('Y-m-d'),
        ]);

    $response->assertStatus(422);
});

/* ========== APPROVE ========== */

it('encargado can approve a flexible request', function () {
    $solicitud = ExtensionRequest::factory()->create([
        'elemento_asignacion_id'  => $this->asignacion->elemento_asignacion_id,
        'evidencia_asignacion_id' => null,
        'usuario_id'              => $this->docente->usuario_id,
        'estado'                  => ExtensionRequest::ESTADO_PENDIENTE,
        'fecha_sugerida'          => Carbon::now()->addDays(20),
    ]);

    $response = $this->actingAs($this->encargado, 'sanctum')
        ->postJson("/api/elemento-solicitudes-ampliacion/{$solicitud->solicitud_ampliacion_id}/aprobar", [
            'justificacion' => 'Se aprueba la extensión solicitada',
        ]);

    $response->assertStatus(200)
        ->assertJson(['data' => ['estado' => ExtensionRequest::ESTADO_APROBADA]]);

    $this->assertDatabaseHas('SOLICITUD_AMPLIACION', [
        'solicitud_ampliacion_id' => $solicitud->solicitud_ampliacion_id,
        'estado'                  => ExtensionRequest::ESTADO_APROBADA,
    ]);
});

it('docente cannot approve a flexible request', function () {
    $solicitud = ExtensionRequest::factory()->create([
        'elemento_asignacion_id'  => $this->asignacion->elemento_asignacion_id,
        'evidencia_asignacion_id' => null,
        'estado'                  => ExtensionRequest::ESTADO_PENDIENTE,
        'usuario_id'              => $this->docente->usuario_id,
    ]);

    $response = $this->actingAs($this->docente, 'sanctum')
        ->postJson("/api/elemento-solicitudes-ampliacion/{$solicitud->solicitud_ampliacion_id}/aprobar");

    $response->assertStatus(403);
});

/* ========== REJECT ========== */

it('encargado can reject a flexible request', function () {
    $solicitud = ExtensionRequest::factory()->create([
        'elemento_asignacion_id'  => $this->asignacion->elemento_asignacion_id,
        'evidencia_asignacion_id' => null,
        'usuario_id'              => $this->docente->usuario_id,
        'estado'                  => ExtensionRequest::ESTADO_PENDIENTE,
    ]);

    $response = $this->actingAs($this->encargado, 'sanctum')
        ->postJson("/api/elemento-solicitudes-ampliacion/{$solicitud->solicitud_ampliacion_id}/rechazar", [
            'justificacion' => 'No cumple los requisitos para la extensión',
        ]);

    $response->assertStatus(200)
        ->assertJson(['data' => ['estado' => ExtensionRequest::ESTADO_RECHAZADA]]);

    $this->assertDatabaseHas('SOLICITUD_AMPLIACION', [
        'solicitud_ampliacion_id' => $solicitud->solicitud_ampliacion_id,
        'estado'                  => ExtensionRequest::ESTADO_RECHAZADA,
    ]);
});

it('cannot reject an already resolved flexible request', function () {
    $solicitud = ExtensionRequest::factory()->aprobada()->create([
        'elemento_asignacion_id'  => $this->asignacion->elemento_asignacion_id,
        'evidencia_asignacion_id' => null,
        'usuario_id'              => $this->docente->usuario_id,
    ]);

    $response = $this->actingAs($this->encargado, 'sanctum')
        ->postJson("/api/elemento-solicitudes-ampliacion/{$solicitud->solicitud_ampliacion_id}/rechazar", [
            'justificacion' => 'Intento rechazar una ya aprobada',
        ]);

    $response->assertStatus(400);
});
