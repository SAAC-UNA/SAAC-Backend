<?php

namespace Tests\Unit;

use App\Models\ExtensionRequest;
use App\Models\EvidenceAssignment;
use App\Models\User;
use App\Services\ExtensionTimeRequestService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;
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
class ExtensionTimeRequestTest extends TestCase
{
    use RefreshDatabase;

    protected $service;
    protected $user;

    /**
     * Configuración inicial antes de cada prueba.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new ExtensionTimeRequestService();
        $this->user = User::factory()->create();
    }

    /**
     * Test: listRequests retorna todas las solicitudes sin filtros.
     */
    public function test_list_requests_retorna_todas_las_solicitudes_sin_filtros()
    {
        ExtensionRequest::factory()->count(5)->create();

        $result = $this->service->listRequests(15, []);

        $this->assertCount(5, $result);
    }

    /**
     * Test: listRequests filtra por usuario_id correctamente.
     */
    public function test_list_requests_filtra_por_usuario_id()
    {
        ExtensionRequest::factory()->count(3)->create([
            'usuario_id' => $this->user->usuario_id
        ]);

        ExtensionRequest::factory()->count(2)->create();

        $result = $this->service->listRequests(15, [
            'usuario_id' => $this->user->usuario_id
        ]);

        $this->assertCount(3, $result);
    }

    /**
     * Test: listRequests filtra por estado correctamente.
     */
    public function test_list_requests_filtra_por_estado()
    {
        ExtensionRequest::factory()->count(2)->pendiente()->create();
        ExtensionRequest::factory()->count(3)->aprobada()->create();
        ExtensionRequest::factory()->count(1)->rechazada()->create();

        $result = $this->service->listRequests(15, [
            'estado' => 'pendiente'
        ]);

        $this->assertCount(2, $result);
    }

    /**
     * Test: listRequests filtra por evidencia_asignacion_id correctamente.
     */
    public function test_list_requests_filtra_por_evidencia_asignacion()
    {
        $evidenceAssignment = EvidenceAssignment::factory()->create();

        ExtensionRequest::factory()->count(2)->create([
            'evidencia_asignacion_id' => $evidenceAssignment->evidencia_asignacion_id
        ]);

        ExtensionRequest::factory()->count(3)->create();

        $result = $this->service->listRequests(15, [
            'evidencia_asignacion_id' => $evidenceAssignment->evidencia_asignacion_id
        ]);

        $this->assertCount(2, $result);
    }

    /**
     * Test: listRequests filtra por rango de fechas correctamente.
     */
    public function test_list_requests_filtra_por_rango_de_fechas()
    {
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
    }

    /**
     * Test: listRequests respeta el parámetro de paginación.
     */
    public function test_list_requests_respeta_paginacion()
    {
        ExtensionRequest::factory()->count(25)->create();

        $result = $this->service->listRequests(10, []);

        $this->assertCount(10, $result);
        $this->assertEquals(25, $result->total());
    }

    /**
     * Test: getRequest retorna solicitud del usuario correcto.
     */
    public function test_get_request_retorna_solicitud_del_usuario_correcto()
    {
        $solicitud = ExtensionRequest::factory()->create([
            'usuario_id' => $this->user->usuario_id
        ]);

        $result = $this->service->getRequest(
            $solicitud->solicitud_ampliacion_id,
            $this->user->usuario_id
        );

        $this->assertNotNull($result);
        $this->assertEquals($solicitud->solicitud_ampliacion_id, $result->solicitud_ampliacion_id);
    }

    /**
     * Test: getRequest retorna null si la solicitud no pertenece al usuario.
     */
    public function test_get_request_retorna_null_si_no_pertenece_al_usuario()
    {
        $otroUsuario = User::factory()->create();

        $solicitud = ExtensionRequest::factory()->create([
            'usuario_id' => $otroUsuario->usuario_id
        ]);

        $result = $this->service->getRequest(
            $solicitud->solicitud_ampliacion_id,
            $this->user->usuario_id
        );

        $this->assertNull($result);
    }

    /**
     * Test: getRequest retorna null si la solicitud no existe.
     */
    public function test_get_request_retorna_null_si_no_existe()
    {
        $result = $this->service->getRequest(999999, $this->user->usuario_id);

        $this->assertNull($result);
    }

    /**
     * Test: getUpcomingEvidences retorna evidencias próximas a vencer.
     */
    public function test_get_upcoming_evidences_retorna_evidencias_proximas_a_vencer()
    {
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
    }

    /**
     * Test: getUpcomingEvidences incluye evidencias vencidas.
     */
    public function test_get_upcoming_evidences_incluye_evidencias_vencidas()
    {
        // Evidencia vencida
        EvidenceAssignment::factory()->create([
            'usuario_id' => $this->user->usuario_id,
            'estado' => 'Pendiente',
            'fecha_limite' => Carbon::now()->subDays(2)
        ]);

        $result = $this->service->getUpcomingEvidences($this->user->usuario_id);

        $this->assertCount(1, $result);
    }

    /**
     * Test: getUpcomingEvidences excluye evidencias completadas.
     */
    public function test_get_upcoming_evidences_excluye_evidencias_completadas()
    {
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
    }

    /**
     * Test: getUpcomingEvidences ordena por fecha límite ascendente.
     */
    public function test_get_upcoming_evidences_ordena_por_fecha_limite()
    {
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
    }

    /**
     * Test: createRequest crea solicitud exitosamente con datos válidos.
     */
    public function test_create_request_crea_solicitud_exitosamente()
    {
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
    }

    /**
     * Test: createRequest falla si la asignación no existe.
     */
    public function test_create_request_falla_si_asignacion_no_existe()
    {
        $this->expectException(ValidationException::class);

        $data = [
            'evidencia_asignacion_id' => 999999,
            'motivo' => 'Necesito más tiempo',
            'fecha_sugerida' => Carbon::now()->addDays(10)->format('Y-m-d')
        ];

        $this->service->createRequest($data, $this->user->usuario_id);
    }

    /**
     * Test: createRequest falla si la evidencia no pertenece al usuario.
     */
    public function test_create_request_falla_si_evidencia_no_pertenece_al_usuario()
    {
        $otroUsuario = User::factory()->create();

        $evidenceAssignment = EvidenceAssignment::factory()->create([
            'usuario_id' => $otroUsuario->usuario_id,
            'fecha_limite' => Carbon::now()->addDays(3)
        ]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('asignadas a usted');

        $data = [
            'evidencia_asignacion_id' => $evidenceAssignment->evidencia_asignacion_id,
            'motivo' => 'Necesito más tiempo',
            'fecha_sugerida' => Carbon::now()->addDays(10)->format('Y-m-d')
        ];

        $this->service->createRequest($data, $this->user->usuario_id);
    }

    /**
     * Test: createRequest falla si la evidencia está aprobada.
     */
    public function test_create_request_falla_si_evidencia_esta_aprobada()
    {
        $evidenceAssignment = EvidenceAssignment::factory()->create([
            'usuario_id' => $this->user->usuario_id,
            'estado' => 'Aprobada',
            'fecha_limite' => Carbon::now()->addDays(3)
        ]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('ya aprobadas');

        $data = [
            'evidencia_asignacion_id' => $evidenceAssignment->evidencia_asignacion_id,
            'motivo' => 'Necesito más tiempo',
            'fecha_sugerida' => Carbon::now()->addDays(10)->format('Y-m-d')
        ];

        $this->service->createRequest($data, $this->user->usuario_id);
    }

    /**
     * Test: createRequest falla si el plazo ya venció.
     */
    public function test_create_request_falla_si_plazo_ya_vencio()
    {
        $evidenceAssignment = EvidenceAssignment::factory()->create([
            'usuario_id' => $this->user->usuario_id,
            'estado' => 'Pendiente',
            'fecha_limite' => Carbon::now()->subDays(2)
        ]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('plazo ya vencido');

        $data = [
            'evidencia_asignacion_id' => $evidenceAssignment->evidencia_asignacion_id,
            'motivo' => 'Necesito más tiempo',
            'fecha_sugerida' => Carbon::now()->addDays(10)->format('Y-m-d')
        ];

        $this->service->createRequest($data, $this->user->usuario_id);
    }

    /**
     * Test: createRequest falla si ya existe solicitud pendiente.
     */
    public function test_create_request_falla_si_ya_existe_solicitud_pendiente()
    {
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

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('solicitud pendiente');

        $data = [
            'evidencia_asignacion_id' => $evidenceAssignment->evidencia_asignacion_id,
            'motivo' => 'Necesito más tiempo',
            'fecha_sugerida' => Carbon::now()->addDays(10)->format('Y-m-d')
        ];

        $this->service->createRequest($data, $this->user->usuario_id);
    }

    /**
     * Test: createRequest falla si fecha sugerida no es posterior a fecha límite.
     */
    public function test_create_request_falla_si_fecha_sugerida_no_es_posterior()
    {
        $evidenceAssignment = EvidenceAssignment::factory()->create([
            'usuario_id' => $this->user->usuario_id,
            'estado' => 'Pendiente',
            'fecha_limite' => Carbon::now()->addDays(10)
        ]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('fecha límite original');

        $data = [
            'evidencia_asignacion_id' => $evidenceAssignment->evidencia_asignacion_id,
            'motivo' => 'Necesito más tiempo',
            'fecha_sugerida' => Carbon::now()->addDays(5)->format('Y-m-d')
        ];

        $this->service->createRequest($data, $this->user->usuario_id);
    }

    /**
     * Test: createRequest falla si la ampliación excede 30 días.
     */
    public function test_create_request_falla_si_ampliacion_excede_30_dias()
    {
        $evidenceAssignment = EvidenceAssignment::factory()->create([
            'usuario_id' => $this->user->usuario_id,
            'estado' => 'Pendiente',
            'fecha_limite' => Carbon::now()->addDays(3)
        ]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('30 días');

        $data = [
            'evidencia_asignacion_id' => $evidenceAssignment->evidencia_asignacion_id,
            'motivo' => 'Necesito mucho más tiempo',
            'fecha_sugerida' => Carbon::now()->addDays(50)->format('Y-m-d')
        ];

        $this->service->createRequest($data, $this->user->usuario_id);
    }

    /**
     * Test: updateRequest actualiza solicitud exitosamente.
     */
    public function test_update_request_actualiza_solicitud_exitosamente()
    {
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
    }

    /**
     * Test: updateRequest falla si la solicitud no existe.
     */
    public function test_update_request_falla_si_no_existe()
    {
        $this->expectException(ValidationException::class);

        $this->service->updateRequest(999999, ['motivo' => 'Nuevo motivo'], $this->user->usuario_id);
    }

    /**
     * Test: updateRequest falla si el usuario no es el dueño.
     */
    public function test_update_request_falla_si_usuario_no_es_dueno()
    {
        $otroUsuario = User::factory()->create();

        $solicitud = ExtensionRequest::factory()->pendiente()->create([
            'usuario_id' => $otroUsuario->usuario_id
        ]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('permisos');

        $this->service->updateRequest(
            $solicitud->solicitud_ampliacion_id,
            ['motivo' => 'Nuevo motivo'],
            $this->user->usuario_id
        );
    }

    /**
     * Test: updateRequest falla si la solicitud no está pendiente.
     */
    public function test_update_request_falla_si_no_esta_pendiente()
    {
        $solicitud = ExtensionRequest::factory()->aprobada()->create([
            'usuario_id' => $this->user->usuario_id
        ]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('pendientes');

        $this->service->updateRequest(
            $solicitud->solicitud_ampliacion_id,
            ['motivo' => 'Nuevo motivo'],
            $this->user->usuario_id
        );
    }

    /**
     * Test: deleteRequest elimina solicitud exitosamente.
     */
    public function test_delete_request_elimina_solicitud_exitosamente()
    {
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
    }

    /**
     * Test: deleteRequest falla si la solicitud no existe.
     */
    public function test_delete_request_falla_si_no_existe()
    {
        $this->expectException(ValidationException::class);

        $this->service->deleteRequest(999999, $this->user->usuario_id);
    }

    /**
     * Test: deleteRequest falla si el usuario no es el dueño.
     */
    public function test_delete_request_falla_si_usuario_no_es_dueno()
    {
        $otroUsuario = User::factory()->create();

        $solicitud = ExtensionRequest::factory()->pendiente()->create([
            'usuario_id' => $otroUsuario->usuario_id
        ]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('propias solicitudes');

        $this->service->deleteRequest(
            $solicitud->solicitud_ampliacion_id,
            $this->user->usuario_id
        );
    }

    /**
     * Test: deleteRequest falla si la solicitud no está pendiente.
     */
    public function test_delete_request_falla_si_no_esta_pendiente()
    {
        $solicitud = ExtensionRequest::factory()->aprobada()->create([
            'usuario_id' => $this->user->usuario_id
        ]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('pendientes');

        $this->service->deleteRequest(
            $solicitud->solicitud_ampliacion_id,
            $this->user->usuario_id
        );
    }
}
