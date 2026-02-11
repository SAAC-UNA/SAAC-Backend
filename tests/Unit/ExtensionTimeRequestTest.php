<?php

use App\Models\ExtensionRequest;
use App\Models\EvidenceAssignment;
use App\Models\User;
use App\Services\ExtensionTimeRequestService;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

/**
 * Pruebas unitarias para ExtensionTimeRequestService (RF-15).
 *
 * Valida la lógica de negocio del servicio:
 * - Listar solicitudes con filtros
 * - Obtener solicitud específica
 * - Crear solicitud con validaciones
 * - Actualizar solicitud pendiente
 * - Eliminar solicitud pendiente
 * - Obtener evidencias próximas a vencer
 */

beforeEach(function() {
    $this->service = new ExtensionTimeRequestService();
    $this->user = User::factory()->create();
});

it('list requests retorna todas las solicitudes sin filtros', function () {
    ExtensionRequest::factory()->count(5)->create();

    $result = $this->service->listRequests(15, []);

    $this->assertCount(5, $result);
});

it('filtra por usuario_id correctamente', function () {
    ExtensionRequest::factory()->count(3)->create([
        'usuario_id' => $this->user->usuario_id
    ]);

    ExtensionRequest::factory()->count(2)->create();

    $result = $this->service->listRequests(15, [
        'usuario_id' => $this->user->usuario_id
    ]);

    $this->assertCount(3, $result);
});

it('filtra por estado correctamente', function () {
    ExtensionRequest::factory()->count(2)->pendiente()->create();
    ExtensionRequest::factory()->count(3)->aprobada()->create();
    ExtensionRequest::factory()->count(1)->rechazada()->create();

    $result = $this->service->listRequests(15, [
        'estado' => 'pendiente'
    ]);

    $this->assertCount(2, $result);
});

it('filtra por evidencia_asignacion_id correctamente', function () {
    $evidenceAssignment = EvidenceAssignment::factory()->create();

    ExtensionRequest::factory()->count(2)->create([
        'evidencia_asignacion_id' => $evidenceAssignment->evidencia_asignacion_id
    ]);

    ExtensionRequest::factory()->count(3)->create();

    $result = $this->service->listRequests(15, [
        'evidencia_asignacion_id' => $evidenceAssignment->evidencia_asignacion_id
    ]);

    $this->assertCount(2, $result);
});

it('filtra por rango de fechas correctamente', function () {
    // Solicitudes nuevas (últimos 3 días)
    ExtensionRequest::factory()->count(2)->create([
        'created_at' => Carbon::now()->subDays(2)
    ]);

    // Solicitudes antiguas (hace 10 días)
    ExtensionRequest::factory()->count(3)->create([
        'created_at' => Carbon::now()->subDays(10)
    ]);

    $result = $this->service->listRequests(15, [
        'fecha_desde' => Carbon::now()->subDays(5)->format('Y-m-d')
    ]);

    $this->assertCount(2, $result);
});

it('respeta el parámetro de paginación', function () {
    ExtensionRequest::factory()->count(25)->create();

    $result = $this->service->listRequests(10, []);

    $this->assertCount(10, $result);
    $this->assertEquals(25, $result->total());
});

it('retorna solicitud del usuario correcto', function () {
    $solicitud = ExtensionRequest::factory()->create([
        'usuario_id' => $this->user->usuario_id
    ]);

    $result = $this->service->getRequest(
        $solicitud->solicitud_ampliacion_id,
        $this->user->usuario_id
    );

    $this->assertNotNull($result);
    $this->assertEquals($solicitud->solicitud_ampliacion_id, $result->solicitud_ampliacion_id);
});

it('retorna null si la solicitud no pertenece al usuario', function () {
    $otroUsuario = User::factory()->create();

    $solicitud = ExtensionRequest::factory()->create([
        'usuario_id' => $otroUsuario->usuario_id
    ]);

    $result = $this->service->getRequest(
        $solicitud->solicitud_ampliacion_id,
        $this->user->usuario_id
    );

    $this->assertNull($result);
});

it('retorna null si la solicitud no existe', function () {
    $result = $this->service->getRequest(999999, $this->user->usuario_id);

    $this->assertNull($result);
});

it('retorna evidencias próximas a vencer', function () {
    // Evidencia próxima a vencer (5 días)
    EvidenceAssignment::factory()->create([
        'usuario_id' => $this->user->usuario_id,
        'estado' => 'Pendiente',
        'fecha_limite' => Carbon::now()->addDays(5)
    ]);

    // Evidencia lejana (no debería aparecer)
    EvidenceAssignment::factory()->create([
        'usuario_id' => $this->user->usuario_id,
        'estado' => 'Pendiente',
        'fecha_limite' => Carbon::now()->addDays(15)
    ]);

    $result = $this->service->getUpcomingEvidences($this->user->usuario_id);

    $this->assertCount(1, $result);
});

it('incluye evidencias vencidas', function () {
    // Evidencia vencida
    EvidenceAssignment::factory()->create([
        'usuario_id' => $this->user->usuario_id,
        'estado' => 'Pendiente',
        'fecha_limite' => Carbon::now()->subDays(2)
    ]);

    $result = $this->service->getUpcomingEvidences($this->user->usuario_id);

    $this->assertCount(1, $result);
});

it('excluye evidencias completadas', function () {
    EvidenceAssignment::factory()->create([
        'usuario_id' => $this->user->usuario_id,
        'estado' => 'Completada',
        'fecha_limite' => Carbon::now()->addDays(5)
    ]);

    EvidenceAssignment::factory()->create([
        'usuario_id' => $this->user->usuario_id,
        'estado' => 'Aprobada',
        'fecha_limite' => Carbon::now()->addDays(5)
    ]);

    $result = $this->service->getUpcomingEvidences($this->user->usuario_id);

    $this->assertCount(0, $result);
});

it('ordena por fecha límite ascendente', function () {
    $evidencia1 = EvidenceAssignment::factory()->create([
        'usuario_id' => $this->user->usuario_id,
        'estado' => 'Pendiente',
        'fecha_limite' => Carbon::now()->addDays(5)
    ]);

    $evidencia2 = EvidenceAssignment::factory()->create([
        'usuario_id' => $this->user->usuario_id,
        'estado' => 'Pendiente',
        'fecha_limite' => Carbon::now()->addDays(2)
    ]);

    $result = $this->service->getUpcomingEvidences($this->user->usuario_id);

    $this->assertEquals($evidencia2->evidencia_asignacion_id, $result->first()->evidencia_asignacion_id);
});

it('crea solicitud exitosamente con datos válidos', function () {
    $evidenceAssignment = EvidenceAssignment::factory()->create([
        'usuario_id' => $this->user->usuario_id,
        'estado' => 'Pendiente',
        'fecha_limite' => Carbon::now()->addDays(3)
    ]);

    $data = [
        'evidencia_asignacion_id' => $evidenceAssignment->evidencia_asignacion_id,
        'motivo' => 'Necesito más tiempo para completar la evidencia por motivos de salud',
        'fecha_sugerida' => Carbon::now()->addDays(10)->format('Y-m-d')
    ];

    $result = $this->service->createRequest($data, $this->user->usuario_id);

    $this->assertInstanceOf(ExtensionRequest::class, $result);
    $this->assertEquals($data['motivo'], $result->motivo);
    $this->assertEquals('pendiente', $result->estado);

    $this->assertDatabaseHas('SOLICITUD_AMPLIACION', [
        'usuario_id' => $this->user->usuario_id,
        'evidencia_asignacion_id' => $evidenceAssignment->evidencia_asignacion_id,
        'estado' => 'pendiente'
    ]);
});

it('falla si la asignación no existe', function () {
    $data = [
        'evidencia_asignacion_id' => 999999,
        'motivo' => 'Necesito más tiempo',
        'fecha_sugerida' => Carbon::now()->addDays(10)->format('Y-m-d')
    ];

    $this->service->createRequest($data, $this->user->usuario_id);
})->throws(ValidationException::class);

it('falla si la evidencia no pertenece al usuario', function () {
    $otroUsuario = User::factory()->create();

    $evidenceAssignment = EvidenceAssignment::factory()->create([
        'usuario_id' => $otroUsuario->usuario_id,
        'fecha_limite' => Carbon::now()->addDays(3)
    ]);

    $data = [
        'evidencia_asignacion_id' => $evidenceAssignment->evidencia_asignacion_id,
        'motivo' => 'Necesito más tiempo',
        'fecha_sugerida' => Carbon::now()->addDays(10)->format('Y-m-d')
    ];

    $this->service->createRequest($data, $this->user->usuario_id);
})->throws(ValidationException::class, 'asignadas a usted');

it('falla si la evidencia está aprobada', function () {
    $evidenceAssignment = EvidenceAssignment::factory()->create([
        'usuario_id' => $this->user->usuario_id,
        'estado' => 'Aprobada',
        'fecha_limite' => Carbon::now()->addDays(3)
    ]);

    $data = [
        'evidencia_asignacion_id' => $evidenceAssignment->evidencia_asignacion_id,
        'motivo' => 'Necesito más tiempo',
        'fecha_sugerida' => Carbon::now()->addDays(10)->format('Y-m-d')
    ];

    $this->service->createRequest($data, $this->user->usuario_id);
})->throws(ValidationException::class, 'ya aprobadas');

it('falla si el plazo ya venció', function () {
    $evidenceAssignment = EvidenceAssignment::factory()->create([
        'usuario_id' => $this->user->usuario_id,
        'estado' => 'Pendiente',
        'fecha_limite' => Carbon::now()->subDays(2)
    ]);

    $data = [
        'evidencia_asignacion_id' => $evidenceAssignment->evidencia_asignacion_id,
        'motivo' => 'Necesito más tiempo',
        'fecha_sugerida' => Carbon::now()->addDays(10)->format('Y-m-d')
    ];

    $this->service->createRequest($data, $this->user->usuario_id);
})->throws(ValidationException::class, 'plazo ya vencido');

it('falla si ya existe solicitud pendiente', function () {
    $evidenceAssignment = EvidenceAssignment::factory()->create([
        'usuario_id' => $this->user->usuario_id,
        'estado' => 'Pendiente',
        'fecha_limite' => Carbon::now()->addDays(3)
    ]);

    // Crear solicitud pendiente existente
    ExtensionRequest::factory()->pendiente()->create([
        'usuario_id' => $this->user->usuario_id,
        'evidencia_asignacion_id' => $evidenceAssignment->evidencia_asignacion_id
    ]);

    $data = [
        'evidencia_asignacion_id' => $evidenceAssignment->evidencia_asignacion_id,
        'motivo' => 'Necesito más tiempo',
        'fecha_sugerida' => Carbon::now()->addDays(10)->format('Y-m-d')
    ];

    $this->service->createRequest($data, $this->user->usuario_id);
})->throws(ValidationException::class, 'solicitud pendiente');

it('falla si fecha sugerida no es posterior a fecha límite', function () {
    $evidenceAssignment = EvidenceAssignment::factory()->create([
        'usuario_id' => $this->user->usuario_id,
        'estado' => 'Pendiente',
        'fecha_limite' => Carbon::now()->addDays(10)
    ]);

    $data = [
        'evidencia_asignacion_id' => $evidenceAssignment->evidencia_asignacion_id,
        'motivo' => 'Necesito más tiempo',
        'fecha_sugerida' => Carbon::now()->addDays(5)->format('Y-m-d')
    ];

    $this->service->createRequest($data, $this->user->usuario_id);
})->throws(ValidationException::class, 'fecha límite original');

it('falla si la ampliación excede 30 días', function () {
    $evidenceAssignment = EvidenceAssignment::factory()->create([
        'usuario_id' => $this->user->usuario_id,
        'estado' => 'Pendiente',
        'fecha_limite' => Carbon::now()->addDays(3)
    ]);

    $data = [
        'evidencia_asignacion_id' => $evidenceAssignment->evidencia_asignacion_id,
        'motivo' => 'Necesito mucho más tiempo',
        'fecha_sugerida' => Carbon::now()->addDays(50)->format('Y-m-d')
    ];

    $this->service->createRequest($data, $this->user->usuario_id);
})->throws(ValidationException::class, '30 días');

it('actualiza solicitud exitosamente', function () {
    $evidenceAssignment = EvidenceAssignment::factory()->create([
        'usuario_id' => $this->user->usuario_id,
        'fecha_limite' => Carbon::now()->addDays(5)
    ]);

    $solicitud = ExtensionRequest::factory()->pendiente()->create([
        'usuario_id' => $this->user->usuario_id,
        'evidencia_asignacion_id' => $evidenceAssignment->evidencia_asignacion_id,
        'motivo' => 'Motivo original',
        'fecha_sugerida' => Carbon::now()->addDays(10)
    ]);

    $data = [
        'motivo' => 'Motivo actualizado con más información',
        'fecha_sugerida' => Carbon::now()->addDays(15)->format('Y-m-d')
    ];

    $result = $this->service->updateRequest(
        $solicitud->solicitud_ampliacion_id,
        $data,
        $this->user->usuario_id
    );

    $this->assertEquals($data['motivo'], $result->motivo);

    $this->assertDatabaseHas('SOLICITUD_AMPLIACION', [
        'solicitud_ampliacion_id' => $solicitud->solicitud_ampliacion_id,
        'motivo' => $data['motivo']
    ]);
});

it('falla al actualizar si la solicitud no existe', function () {
    $this->service->updateRequest(999999, ['motivo' => 'Nuevo motivo'], $this->user->usuario_id);
})->throws(ValidationException::class);

it('falla al actualizar si el usuario no es el dueño', function () {
    $otroUsuario = User::factory()->create();

    $solicitud = ExtensionRequest::factory()->pendiente()->create([
        'usuario_id' => $otroUsuario->usuario_id
    ]);

    $this->service->updateRequest(
        $solicitud->solicitud_ampliacion_id,
        ['motivo' => 'Nuevo motivo'],
        $this->user->usuario_id
    );
})->throws(ValidationException::class, 'permisos');

it('falla al actualizar si la solicitud no está pendiente', function () {
    $solicitud = ExtensionRequest::factory()->aprobada()->create([
        'usuario_id' => $this->user->usuario_id
    ]);

    $this->service->updateRequest(
        $solicitud->solicitud_ampliacion_id,
        ['motivo' => 'Nuevo motivo'],
        $this->user->usuario_id
    );
})->throws(ValidationException::class, 'pendientes');

it('elimina solicitud exitosamente', function () {
    $solicitud = ExtensionRequest::factory()->pendiente()->create([
        'usuario_id' => $this->user->usuario_id
    ]);

    $result = $this->service->deleteRequest(
        $solicitud->solicitud_ampliacion_id,
        $this->user->usuario_id
    );

    $this->assertTrue($result);

    $this->assertDatabaseMissing('SOLICITUD_AMPLIACION', [
        'solicitud_ampliacion_id' => $solicitud->solicitud_ampliacion_id
    ]);
});

it('falla al eliminar si la solicitud no existe', function () {
    $this->service->deleteRequest(999999, $this->user->usuario_id);
})->throws(ValidationException::class);

it('falla al eliminar si el usuario no es el dueño', function () {
    $otroUsuario = User::factory()->create();

    $solicitud = ExtensionRequest::factory()->pendiente()->create([
        'usuario_id' => $otroUsuario->usuario_id
    ]);

    $this->service->deleteRequest(
        $solicitud->solicitud_ampliacion_id,
        $this->user->usuario_id
    );
})->throws(ValidationException::class, 'propias solicitudes');

it('falla al eliminar si la solicitud no está pendiente', function () {
    $solicitud = ExtensionRequest::factory()->aprobada()->create([
        'usuario_id' => $this->user->usuario_id
    ]);

    $this->service->deleteRequest(
        $solicitud->solicitud_ampliacion_id,
        $this->user->usuario_id
    );
})->throws(ValidationException::class, 'pendientes');
