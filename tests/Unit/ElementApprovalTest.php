<?php

namespace Tests\Unit;

use App\Models\AccreditationCycle;
use App\Models\CareerCampus;
use App\Models\ElementApproval;
use App\Models\Process;
use App\Models\StructureElement;
use App\Models\StructureModel;
use App\Models\User;
use App\Services\ElementApprovalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Tests unitarios para ElementApprovalService — HU-010 modelo flexible.
 */
class ElementApprovalTest extends TestCase
{
    use RefreshDatabase;

    private ElementApprovalService $service;
    private Process $proceso;
    private StructureElement $elemento;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new ElementApprovalService();

        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);

        $modelo         = StructureModel::factory()->create();
        $careerCampus   = CareerCampus::factory()->create();
        $ciclo          = AccreditationCycle::factory()->create([
            'carrera_sede_id'      => $careerCampus->carrera_sede_id,
            'modelo_estructura_id' => $modelo->modelo_estructura_id,
        ]);
        $this->proceso  = Process::factory()->create([
            'ciclo_acreditacion_id' => $ciclo->ciclo_acreditacion_id,
        ]);
        $this->elemento = StructureElement::factory()->create([
            'modelo_estructura_id' => $modelo->modelo_estructura_id,
            'padre_id'             => null,
            'activo'               => true,
        ]);
    }

    // ─── approveElemento — excepciones ──────────────────────────────────────

    public function test_approveElemento_lanza_excepcion_si_elemento_no_existe(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/no existe/i');

        $this->service->approveElemento(999999, $this->proceso->proceso_id);
    }

    public function test_approveElemento_lanza_excepcion_si_ya_fue_aprobado(): void
    {
        // Aprobar la primera vez
        $this->service->approveElemento($this->elemento->elemento_id, $this->proceso->proceso_id);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/ya está aprobado/i');

        // Segunda aprobación debe fallar
        $this->service->approveElemento($this->elemento->elemento_id, $this->proceso->proceso_id);
    }

    // ─── approveElemento — flujo feliz ───────────────────────────────────────

    public function test_approveElemento_crea_registro_en_APROBACION_ELEMENTO(): void
    {
        $result = $this->service->approveElemento(
            $this->elemento->elemento_id,
            $this->proceso->proceso_id,
            'Comentario de prueba'
        );

        $this->assertArrayHasKey('raiz', $result);
        $this->assertArrayHasKey('cascada', $result);
        $this->assertEquals('aprobado', $result['raiz']->estado);
        $this->assertEmpty($result['cascada']);

        $this->assertDatabaseHas('APROBACION_ELEMENTO', [
            'elemento_id' => $this->elemento->elemento_id,
            'proceso_id'  => $this->proceso->proceso_id,
            'estado'      => 'aprobado',
        ]);
    }

    public function test_approveElemento_asigna_el_usuario_autenticado(): void
    {
        $this->service->approveElemento($this->elemento->elemento_id, $this->proceso->proceso_id);

        $this->assertDatabaseHas('APROBACION_ELEMENTO', [
            'elemento_id' => $this->elemento->elemento_id,
            'proceso_id'  => $this->proceso->proceso_id,
            'usuario_id'  => $this->user->usuario_id,
        ]);
    }

    public function test_approveElemento_cascada_aprueba_hijos_activos(): void
    {
        $hijo1 = StructureElement::factory()->create([
            'modelo_estructura_id' => $this->elemento->modelo_estructura_id,
            'padre_id'             => $this->elemento->elemento_id,
            'activo'               => true,
        ]);
        $hijo2 = StructureElement::factory()->create([
            'modelo_estructura_id' => $this->elemento->modelo_estructura_id,
            'padre_id'             => $this->elemento->elemento_id,
            'activo'               => true,
        ]);

        $result = $this->service->approveElemento(
            $this->elemento->elemento_id,
            $this->proceso->proceso_id
        );

        $this->assertCount(2, $result['cascada']);

        $this->assertDatabaseHas('APROBACION_ELEMENTO', [
            'elemento_id' => $hijo1->elemento_id,
            'estado'      => 'aprobado',
        ]);
        $this->assertDatabaseHas('APROBACION_ELEMENTO', [
            'elemento_id' => $hijo2->elemento_id,
            'estado'      => 'aprobado',
        ]);
    }

    public function test_approveElemento_no_aprueba_hijos_inactivos(): void
    {
        $hijoInactivo = StructureElement::factory()->create([
            'modelo_estructura_id' => $this->elemento->modelo_estructura_id,
            'padre_id'             => $this->elemento->elemento_id,
            'activo'               => false,
        ]);

        $result = $this->service->approveElemento(
            $this->elemento->elemento_id,
            $this->proceso->proceso_id
        );

        $this->assertEmpty($result['cascada']);

        $this->assertDatabaseMissing('APROBACION_ELEMENTO', [
            'elemento_id' => $hijoInactivo->elemento_id,
        ]);
    }

    // ─── rejectElemento — excepciones ────────────────────────────────────────

    public function test_rejectElemento_lanza_excepcion_si_elemento_no_existe(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/no existe/i');

        $this->service->rejectElemento(999999, $this->proceso->proceso_id);
    }

    public function test_rejectElemento_lanza_excepcion_si_ya_fue_rechazado(): void
    {
        $this->service->rejectElemento($this->elemento->elemento_id, $this->proceso->proceso_id);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/ya está rechazado/i');

        $this->service->rejectElemento($this->elemento->elemento_id, $this->proceso->proceso_id);
    }

    // ─── rejectElemento — flujo feliz ─────────────────────────────────────────

    public function test_rejectElemento_crea_registro_en_APROBACION_ELEMENTO(): void
    {
        $resultado = $this->service->rejectElemento(
            $this->elemento->elemento_id,
            $this->proceso->proceso_id,
            'Falta documentación',
            now()->addDays(10)->toDateString()
        );

        $this->assertEquals('rechazado', $resultado['raiz']->estado);

        $this->assertDatabaseHas('APROBACION_ELEMENTO', [
            'elemento_id' => $this->elemento->elemento_id,
            'proceso_id'  => $this->proceso->proceso_id,
            'estado'      => 'rechazado',
        ]);
    }

    // ─── recalculateParentState ───────────────────────────────────────────────

    public function test_recalculateParentState_estado_aprobado_cuando_todos_hijos_aprobados(): void
    {
        $hijo1 = StructureElement::factory()->create([
            'modelo_estructura_id' => $this->elemento->modelo_estructura_id,
            'padre_id'             => $this->elemento->elemento_id,
            'activo'               => true,
        ]);
        $hijo2 = StructureElement::factory()->create([
            'modelo_estructura_id' => $this->elemento->modelo_estructura_id,
            'padre_id'             => $this->elemento->elemento_id,
            'activo'               => true,
        ]);

        // Crear aprobación del padre (necesaria para recalcular)
        ElementApproval::factory()->create([
            'elemento_id' => $this->elemento->elemento_id,
            'proceso_id'  => $this->proceso->proceso_id,
            'usuario_id'  => $this->user->usuario_id,
            'estado'      => 'pendiente',
        ]);

        // Crear aprobaciones de ambos hijos como 'aprobado'
        ElementApproval::factory()->create([
            'elemento_id' => $hijo1->elemento_id,
            'proceso_id'  => $this->proceso->proceso_id,
            'usuario_id'  => $this->user->usuario_id,
            'estado'      => 'aprobado',
        ]);
        ElementApproval::factory()->create([
            'elemento_id' => $hijo2->elemento_id,
            'proceso_id'  => $this->proceso->proceso_id,
            'usuario_id'  => $this->user->usuario_id,
            'estado'      => 'aprobado',
        ]);

        $this->service->recalculateParentState($this->elemento->elemento_id, $this->proceso->proceso_id);

        $this->assertDatabaseHas('APROBACION_ELEMENTO', [
            'elemento_id' => $this->elemento->elemento_id,
            'estado'      => 'aprobado',
        ]);
    }

    public function test_recalculateParentState_estado_rechazado_cuando_todos_hijos_rechazados(): void
    {
        $hijo = StructureElement::factory()->create([
            'modelo_estructura_id' => $this->elemento->modelo_estructura_id,
            'padre_id'             => $this->elemento->elemento_id,
            'activo'               => true,
        ]);

        ElementApproval::factory()->create([
            'elemento_id' => $this->elemento->elemento_id,
            'proceso_id'  => $this->proceso->proceso_id,
            'usuario_id'  => $this->user->usuario_id,
            'estado'      => 'pendiente',
        ]);
        ElementApproval::factory()->create([
            'elemento_id' => $hijo->elemento_id,
            'proceso_id'  => $this->proceso->proceso_id,
            'usuario_id'  => $this->user->usuario_id,
            'estado'      => 'rechazado',
        ]);

        $this->service->recalculateParentState($this->elemento->elemento_id, $this->proceso->proceso_id);

        $this->assertDatabaseHas('APROBACION_ELEMENTO', [
            'elemento_id' => $this->elemento->elemento_id,
            'estado'      => 'rechazado',
        ]);
    }

    public function test_recalculateParentState_estado_incompleto_cuando_hay_mezcla(): void
    {
        $hijo1 = StructureElement::factory()->create([
            'modelo_estructura_id' => $this->elemento->modelo_estructura_id,
            'padre_id'             => $this->elemento->elemento_id,
            'activo'               => true,
        ]);
        $hijo2 = StructureElement::factory()->create([
            'modelo_estructura_id' => $this->elemento->modelo_estructura_id,
            'padre_id'             => $this->elemento->elemento_id,
            'activo'               => true,
        ]);

        ElementApproval::factory()->create([
            'elemento_id' => $this->elemento->elemento_id,
            'proceso_id'  => $this->proceso->proceso_id,
            'usuario_id'  => $this->user->usuario_id,
            'estado'      => 'pendiente',
        ]);
        ElementApproval::factory()->create([
            'elemento_id' => $hijo1->elemento_id,
            'proceso_id'  => $this->proceso->proceso_id,
            'usuario_id'  => $this->user->usuario_id,
            'estado'      => 'aprobado',
        ]);
        ElementApproval::factory()->create([
            'elemento_id' => $hijo2->elemento_id,
            'proceso_id'  => $this->proceso->proceso_id,
            'usuario_id'  => $this->user->usuario_id,
            'estado'      => 'rechazado',
        ]);

        $this->service->recalculateParentState($this->elemento->elemento_id, $this->proceso->proceso_id);

        $this->assertDatabaseHas('APROBACION_ELEMENTO', [
            'elemento_id' => $this->elemento->elemento_id,
            'estado'      => 'incompleto',
        ]);
    }

    // ─── listApprovals / getApproval ─────────────────────────────────────────

    public function test_listApprovals_retorna_todos_los_registros(): void
    {
        ElementApproval::factory()->count(3)->create([
            'proceso_id' => $this->proceso->proceso_id,
            'usuario_id' => $this->user->usuario_id,
        ]);

        $result = $this->service->listApprovals();

        $this->assertGreaterThanOrEqual(3, $result->count());
    }

    public function test_listApprovals_filtra_por_estado(): void
    {
        ElementApproval::factory()->create([
            'proceso_id' => $this->proceso->proceso_id,
            'usuario_id' => $this->user->usuario_id,
            'estado'     => 'aprobado',
        ]);
        ElementApproval::factory()->rechazado()->create([
            'proceso_id' => $this->proceso->proceso_id,
            'usuario_id' => $this->user->usuario_id,
        ]);

        $result = $this->service->listApprovals('aprobado');

        $this->assertTrue($result->every(fn($a) => $a->estado === 'aprobado'));
    }

    public function test_getApproval_retorna_null_si_no_existe(): void
    {
        $result = $this->service->getApproval(999999);

        $this->assertNull($result);
    }
}
