<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\ExtensionRequest;
use App\Models\EvidenceAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;

/**
 * Pruebas Unitarias para el modelo ExtensionRequest
 * 
 * Verifica:
 * - Relaciones del modelo
 * - Scopes (filtros predefinidos)
 * - Constantes de estados
 * - Casts de fechas
 */
class ExtensionRequestTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function testHasCorrectTableName()
    {
        $solicitud = new ExtensionRequest();
        $this->assertEquals('SOLICITUD_AMPLIACION', $solicitud->getTable());
    }

    #[Test]
    public function testHasCorrectPrimaryKey()
    {
        $solicitud = new ExtensionRequest();
        $this->assertEquals('solicitud_ampliacion_id', $solicitud->getKeyName());
    }

    #[Test]
    public function testHasFillableAttributes()
    {
        $solicitud = new ExtensionRequest();
        $fillable = [
            'evidencia_asignacion_id',
            'usuario_id',
            'fecha_solicitud',
            'motivo',
            'fecha_sugerida',
            'estado',
            'fecha_resolucion',
            'usuario_resolutor_id',
            'justificacion'
        ];

        $this->assertEquals($fillable, $solicitud->getFillable());
    }

    #[Test]
    public function testCastsDatesCorrectly()
    {
        $solicitud = ExtensionRequest::factory()->create([
            'fecha_solicitud' => '2026-01-15 10:30:00',
            'fecha_sugerida' => '2026-02-01 23:59:59',
        ]);

        $this->assertInstanceOf(\Carbon\Carbon::class, $solicitud->fecha_solicitud);
        $this->assertInstanceOf(\Carbon\Carbon::class, $solicitud->fecha_sugerida);
        $this->assertEquals('2026-01-15', $solicitud->fecha_solicitud->format('Y-m-d'));
        $this->assertEquals('2026-02-01', $solicitud->fecha_sugerida->format('Y-m-d'));
    }

    #[Test]
    public function testHasEstadoConstants()
    {
        $this->assertEquals('pendiente', ExtensionRequest::ESTADO_PENDIENTE);
        $this->assertEquals('aprobada', ExtensionRequest::ESTADO_APROBADA);
        $this->assertEquals('rechazada', ExtensionRequest::ESTADO_RECHAZADA);
    }

    #[Test]
    public function testBelongsToEvidenceAssignment()
    {
        $asignacion = EvidenceAssignment::factory()->create();
        $solicitud = ExtensionRequest::factory()->create([
            'evidencia_asignacion_id' => $asignacion->evidencia_asignacion_id
        ]);

        $this->assertInstanceOf(EvidenceAssignment::class, $solicitud->evidenceAssignment);
        $this->assertEquals($asignacion->evidencia_asignacion_id, $solicitud->evidenceAssignment->evidencia_asignacion_id);
    }

    #[Test]
    public function testBelongsToUser()
    {
        $usuario = User::factory()->create();
        $solicitud = ExtensionRequest::factory()->create([
            'usuario_id' => $usuario->usuario_id
        ]);

        $this->assertInstanceOf(User::class, $solicitud->user);
        $this->assertEquals($usuario->usuario_id, $solicitud->user->usuario_id);
    }

    #[Test]
    public function testBelongsToResolutor()
    {
        $resolutor = User::factory()->create();
        $solicitud = ExtensionRequest::factory()->aprobada()->create([
            'usuario_resolutor_id' => $resolutor->usuario_id
        ]);

        $this->assertInstanceOf(User::class, $solicitud->resolutor);
        $this->assertEquals($resolutor->usuario_id, $solicitud->resolutor->usuario_id);
    }

    #[Test]
    public function testResolutorCanBeNullForPendingRequests()
    {
        $solicitud = ExtensionRequest::factory()->pendiente()->create();

        $this->assertNull($solicitud->usuario_resolutor_id);
        $this->assertNull($solicitud->resolutor);
    }

    #[Test]
    public function testScopePendientesFiltersPendingRequests()
    {
        ExtensionRequest::factory()->pendiente()->count(3)->create();
        ExtensionRequest::factory()->aprobada()->count(2)->create();
        ExtensionRequest::factory()->rechazada()->count(1)->create();

        $pendientes = ExtensionRequest::pendientes()->get();

        $this->assertCount(3, $pendientes);
        $this->assertTrue($pendientes->every(function ($solicitud) {
            return $solicitud->estado === ExtensionRequest::ESTADO_PENDIENTE;
        }));
    }

    #[Test]
    public function testScopeAprobadasFiltersApprovedRequests()
    {
        ExtensionRequest::factory()->pendiente()->count(2)->create();
        ExtensionRequest::factory()->aprobada()->count(4)->create();
        ExtensionRequest::factory()->rechazada()->count(1)->create();

        $aprobadas = ExtensionRequest::aprobadas()->get();

        $this->assertCount(4, $aprobadas);
        $this->assertTrue($aprobadas->every(function ($solicitud) {
            return $solicitud->estado === ExtensionRequest::ESTADO_APROBADA;
        }));
    }

    #[Test]
    public function testScopeRechazadasFiltersRejectedRequests()
    {
        ExtensionRequest::factory()->pendiente()->count(2)->create();
        ExtensionRequest::factory()->aprobada()->count(2)->create();
        ExtensionRequest::factory()->rechazada()->count(3)->create();

        $rechazadas = ExtensionRequest::rechazadas()->get();

        $this->assertCount(3, $rechazadas);
        $this->assertTrue($rechazadas->every(function ($solicitud) {
            return $solicitud->estado === ExtensionRequest::ESTADO_RECHAZADA;
        }));
    }

    #[Test]
    public function testCanCreateExtensionRequestWithAllRequiredFields()
    {
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
    }

    #[Test]
    public function testPendingRequestHasNullResolutionFields()
    {
        $solicitud = ExtensionRequest::factory()->pendiente()->create();

        $this->assertNull($solicitud->fecha_resolucion);
        $this->assertNull($solicitud->usuario_resolutor_id);
        $this->assertNull($solicitud->justificacion);
    }

    #[Test]
    public function testApprovedRequestHasResolutionFields()
    {
        $solicitud = ExtensionRequest::factory()->aprobada()->create();

        $this->assertNotNull($solicitud->fecha_resolucion);
        $this->assertNotNull($solicitud->usuario_resolutor_id);
        $this->assertNotNull($solicitud->justificacion);
        $this->assertEquals(ExtensionRequest::ESTADO_APROBADA, $solicitud->estado);
    }

    #[Test]
    public function testRejectedRequestHasResolutionFields()
    {
        $solicitud = ExtensionRequest::factory()->rechazada()->create();

        $this->assertNotNull($solicitud->fecha_resolucion);
        $this->assertNotNull($solicitud->usuario_resolutor_id);
        $this->assertNotNull($solicitud->justificacion);
        $this->assertEquals(ExtensionRequest::ESTADO_RECHAZADA, $solicitud->estado);
    }

    #[Test]
    public function testCanUpdateEstadoFromPendienteToAprobada()
    {
        $solicitud = ExtensionRequest::factory()->pendiente()->create();
        $resolutor = User::factory()->create();

        $solicitud->update([
            'estado' => ExtensionRequest::ESTADO_APROBADA,
            'fecha_resolucion' => Carbon::now(),
            'usuario_resolutor_id' => $resolutor->usuario_id,
            'justificacion' => 'Aprobada porque...',
        ]);

        $this->assertEquals(ExtensionRequest::ESTADO_APROBADA, $solicitud->estado);
        $this->assertNotNull($solicitud->fecha_resolucion);
    }

    #[Test]
    public function testCanUpdateEstadoFromPendienteToRechazada()
    {
        $solicitud = ExtensionRequest::factory()->pendiente()->create();
        $resolutor = User::factory()->create();

        $solicitud->update([
            'estado' => ExtensionRequest::ESTADO_RECHAZADA,
            'fecha_resolucion' => Carbon::now(),
            'usuario_resolutor_id' => $resolutor->usuario_id,
            'justificacion' => 'Rechazada porque...',
        ]);

        $this->assertEquals(ExtensionRequest::ESTADO_RECHAZADA, $solicitud->estado);
        $this->assertNotNull($solicitud->fecha_resolucion);
    }

    #[Test]
    public function testFactoryCreatesValidExtensionRequest()
    {
        $solicitud = ExtensionRequest::factory()->create();

        $this->assertNotNull($solicitud->evidencia_asignacion_id);
        $this->assertNotNull($solicitud->usuario_id);
        $this->assertNotNull($solicitud->fecha_solicitud);
        $this->assertNotNull($solicitud->motivo);
        $this->assertNotNull($solicitud->fecha_sugerida);
        $this->assertNotNull($solicitud->estado);
        $this->assertContains($solicitud->estado, [
            ExtensionRequest::ESTADO_PENDIENTE,
            ExtensionRequest::ESTADO_APROBADA,
            ExtensionRequest::ESTADO_RECHAZADA
        ]);
    }
}
