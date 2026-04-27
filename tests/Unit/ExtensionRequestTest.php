<?php

use Tests\TestCase;
use App\Models\ExtensionRequest;
use App\Models\EvidenceAssignment;
use App\Models\User;
use Carbon\Carbon;


/**
 * Pruebas Unitarias para el modelo ExtensionRequest
 * 
 * Verifica:
 * - Relaciones del modelo
 * - Scopes (filtros predefinidos)
 * - Constantes de estados
 * - Casts de fechas
 */

it('has correct table name', function () {
    $solicitud = new ExtensionRequest();
    expect($solicitud->getTable())->toBe('SOLICITUD_AMPLIACION');
});

it('has correct primary key', function () {
    $solicitud = new ExtensionRequest();
    expect($solicitud->getKeyName())->toBe('solicitud_ampliacion_id');
});

it('has fillable attributes', function () {
    $solicitud = new ExtensionRequest();
    $fillable = [
        'evidencia_asignacion_id',
        'elemento_asignacion_id',
        'usuario_id',
        'motivo',
        'fecha_sugerida',
        'estado',
        'fecha_resolucion',
        'usuario_resolutor_id',
        'justificacion'
    ];

    expect($solicitud->getFillable())->toBe($fillable);
});

it('casts dates correctly', function () {
    $solicitud = ExtensionRequest::factory()->create([
        'fecha_sugerida' => '2026-02-01 23:59:59',
    ]);

    $this->assertInstanceOf(\Carbon\Carbon::class, $solicitud->created_at);
    $this->assertInstanceOf(\Carbon\Carbon::class, $solicitud->fecha_sugerida);
    expect($solicitud->fecha_sugerida->format('Y-m-d'))->toBe('2026-02-01');
});

it('has estado constants', function () {
    expect(ExtensionRequest::ESTADO_PENDIENTE)->toBe('pendiente');
    expect(ExtensionRequest::ESTADO_APROBADA)->toBe('aprobada');
    expect(ExtensionRequest::ESTADO_RECHAZADA)->toBe('rechazada');
});

it('belongs to evidence assignment', function () {
    $asignacion = EvidenceAssignment::factory()->create();
    $solicitud = ExtensionRequest::factory()->create([
        'evidencia_asignacion_id' => $asignacion->evidencia_asignacion_id
    ]);

    $this->assertInstanceOf(EvidenceAssignment::class, $solicitud->evidenceAssignment);
    expect($solicitud->evidenceAssignment->evidencia_asignacion_id)->toBe($asignacion->evidencia_asignacion_id);
});

it('belongs to user', function () {
    $usuario = User::factory()->create();
    $solicitud = ExtensionRequest::factory()->create([
        'usuario_id' => $usuario->usuario_id
    ]);

    $this->assertInstanceOf(User::class, $solicitud->user);
    expect($solicitud->user->usuario_id)->toBe($usuario->usuario_id);
});

it('belongs to resolutor', function () {
    $resolutor = User::factory()->create();
    $solicitud = ExtensionRequest::factory()->aprobada()->create([
        'usuario_resolutor_id' => $resolutor->usuario_id
    ]);

    $this->assertInstanceOf(User::class, $solicitud->resolutor);
    expect($solicitud->resolutor->usuario_id)->toBe($resolutor->usuario_id);
});

it('resolutor can be null for pending requests', function () {
    $solicitud = ExtensionRequest::factory()->pendiente()->create();

    $this->assertNull($solicitud->usuario_resolutor_id);
    $this->assertNull($solicitud->resolutor);
});

it('scope pendientes filters pending requests', function () {
    ExtensionRequest::factory()->pendiente()->count(3)->create();
    ExtensionRequest::factory()->aprobada()->count(2)->create();
    ExtensionRequest::factory()->rechazada()->count(1)->create();

    $pendientes = ExtensionRequest::pendientes()->get();

    $this->assertCount(3, $pendientes);
    expect($pendientes->every(function ($solicitud) {
        return $solicitud->estado === ExtensionRequest::ESTADO_PENDIENTE;
    }))->toBeTrue();
});

it('scope aprobadas filters approved requests', function () {
    ExtensionRequest::factory()->pendiente()->count(2)->create();
    ExtensionRequest::factory()->aprobada()->count(4)->create();
    ExtensionRequest::factory()->rechazada()->count(1)->create();

    $aprobadas = ExtensionRequest::aprobadas()->get();

    $this->assertCount(4, $aprobadas);
    expect($aprobadas->every(function ($solicitud) {
        return $solicitud->estado === ExtensionRequest::ESTADO_APROBADA;
    }))->toBeTrue();
});

it('scope rechazadas filters rejected requests', function () {
    ExtensionRequest::factory()->pendiente()->count(2)->create();
    ExtensionRequest::factory()->aprobada()->count(2)->create();
    ExtensionRequest::factory()->rechazada()->count(3)->create();

    $rechazadas = ExtensionRequest::rechazadas()->get();

    $this->assertCount(3, $rechazadas);
    expect($rechazadas->every(function ($solicitud) {
        return $solicitud->estado === ExtensionRequest::ESTADO_RECHAZADA;
    }))->toBeTrue();
});

it('can create extension request with all required fields', function () {
    $asignacion = EvidenceAssignment::factory()->create();
    $usuario = User::factory()->create();

    $solicitud = ExtensionRequest::create([
        'evidencia_asignacion_id' => $asignacion->evidencia_asignacion_id,
        'usuario_id' => $usuario->usuario_id,
        'fecha_solicitud' => Carbon::now(),
        'motivo' => 'Necesito más tiempo para completar la evidencia',
        'fecha_sugerida' => Carbon::now()->addDays(7),
        'estado' => ExtensionRequest::ESTADO_PENDIENTE,
    ]);

    $this->assertDatabaseHas('SOLICITUD_AMPLIACION', [
        'solicitud_ampliacion_id' => $solicitud->solicitud_ampliacion_id,
        'estado' => ExtensionRequest::ESTADO_PENDIENTE,
    ]);
});

it('pending request has null resolution fields', function () {
    $solicitud = ExtensionRequest::factory()->pendiente()->create();

    $this->assertNull($solicitud->fecha_resolucion);
    $this->assertNull($solicitud->usuario_resolutor_id);
    $this->assertNull($solicitud->justificacion);
});

it('approved request has resolution fields', function () {
    $solicitud = ExtensionRequest::factory()->aprobada()->create();

    $this->assertNotNull($solicitud->fecha_resolucion);
    $this->assertNotNull($solicitud->usuario_resolutor_id);
    $this->assertNotNull($solicitud->justificacion);
    expect($solicitud->estado)->toBe(ExtensionRequest::ESTADO_APROBADA);
});

it('rejected request has resolution fields', function () {
    $solicitud = ExtensionRequest::factory()->rechazada()->create();

    $this->assertNotNull($solicitud->fecha_resolucion);
    $this->assertNotNull($solicitud->usuario_resolutor_id);
    $this->assertNotNull($solicitud->justificacion);
    expect($solicitud->estado)->toBe(ExtensionRequest::ESTADO_RECHAZADA);
});

it('can update estado from pendiente to aprobada', function () {
    $solicitud = ExtensionRequest::factory()->pendiente()->create();
    $resolutor = User::factory()->create();

    $solicitud->update([
        'estado' => ExtensionRequest::ESTADO_APROBADA,
        'fecha_resolucion' => Carbon::now(),
        'usuario_resolutor_id' => $resolutor->usuario_id,
        'justificacion' => 'Aprobada porque...',
    ]);

    expect($solicitud->estado)->toBe(ExtensionRequest::ESTADO_APROBADA);
    $this->assertNotNull($solicitud->fecha_resolucion);
});

it('can update estado from pendiente to rechazada', function () {
    $solicitud = ExtensionRequest::factory()->pendiente()->create();
    $resolutor = User::factory()->create();

    $solicitud->update([
        'estado' => ExtensionRequest::ESTADO_RECHAZADA,
        'fecha_resolucion' => Carbon::now(),
        'usuario_resolutor_id' => $resolutor->usuario_id,
        'justificacion' => 'Rechazada porque...',
    ]);

    expect($solicitud->estado)->toBe(ExtensionRequest::ESTADO_RECHAZADA);
    $this->assertNotNull($solicitud->fecha_resolucion);
});

it('factory creates valid extension request', function () {
    $solicitud = ExtensionRequest::factory()->create();

    $this->assertNotNull($solicitud->evidencia_asignacion_id);
    $this->assertNotNull($solicitud->usuario_id);
    $this->assertNotNull($solicitud->created_at);
    $this->assertNotNull($solicitud->motivo);
    $this->assertNotNull($solicitud->fecha_sugerida);
    $this->assertNotNull($solicitud->estado);
    $this->assertContains($solicitud->estado, [
        ExtensionRequest::ESTADO_PENDIENTE,
        ExtensionRequest::ESTADO_APROBADA,
        ExtensionRequest::ESTADO_RECHAZADA
    ]);
});
