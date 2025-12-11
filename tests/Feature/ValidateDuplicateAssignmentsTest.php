<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Process;
use App\Models\Evidence;
use App\Models\EvidenceAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;

class ValidateDuplicateAssignmentsTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $process;
    protected $evidence;
    protected $usuarios;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear usuario autenticado (el permiso se verifica en FormRequest)
        $this->user = User::factory()->create();

        // Crear permiso y asignarlo
        $permission = \Spatie\Permission\Models\Permission::create(['name' => 'asignar_evidencias', 'guard_name' => 'api']);
        $this->user->givePermissionTo($permission);

        // Crear proceso y evidencia
        $this->process = Process::factory()->create();
        $this->evidence = Evidence::factory()->create();

        // Crear 5 usuarios de prueba
        $this->usuarios = User::factory()->count(5)->create();
    }

    #[Test]
    public function puede_validar_sin_duplicados()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/evidencias-asignaciones/validar-duplicados', [
                'proceso_id' => $this->process->proceso_id,
                'evidencia_id' => $this->evidence->evidencia_id,
                'usuarios' => $this->usuarios->pluck('usuario_id')->toArray(),
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'tiene_duplicados' => false,
                'duplicados' => [],
                'total_duplicados' => 0,
            ]);
    }

    #[Test]
    public function detecta_asignaciones_duplicadas()
    {
        // Crear 2 asignaciones existentes
        $usuario1 = $this->usuarios[0];
        $usuario2 = $this->usuarios[1];

        EvidenceAssignment::create([
            'proceso_id' => $this->process->proceso_id,
            'evidencia_id' => $this->evidence->evidencia_id,
            'usuario_id' => $usuario1->usuario_id,
            'estado' => 'pendiente',
            'fecha_asignacion' => now(),
        ]);

        EvidenceAssignment::create([
            'proceso_id' => $this->process->proceso_id,
            'evidencia_id' => $this->evidence->evidencia_id,
            'usuario_id' => $usuario2->usuario_id,
            'estado' => 'en_progreso',
            'fecha_asignacion' => now()->subDays(3),
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/evidencias-asignaciones/validar-duplicados', [
                'proceso_id' => $this->process->proceso_id,
                'evidencia_id' => $this->evidence->evidencia_id,
                'usuarios' => $this->usuarios->pluck('usuario_id')->toArray(),
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'tiene_duplicados' => true,
                'total_duplicados' => 2,
            ])
            ->assertJsonCount(2, 'duplicados');

        // Verificar estructura de duplicados
        $duplicados = $response->json('duplicados');
        
        $this->assertArrayHasKey('usuario_id', $duplicados[0]);
        $this->assertArrayHasKey('usuario_nombre', $duplicados[0]);
        $this->assertArrayHasKey('estado', $duplicados[0]);
        $this->assertArrayHasKey('fecha_asignacion', $duplicados[0]);
        $this->assertArrayHasKey('asignacion_id', $duplicados[0]);
    }

    #[Test]
    public function requiere_autenticacion()
    {
        $response = $this->postJson('/api/evidencias-asignaciones/validar-duplicados', [
            'proceso_id' => $this->process->proceso_id,
            'evidencia_id' => $this->evidence->evidencia_id,
            'usuarios' => [1, 2, 3],
        ]);

        $response->assertStatus(401);
    }

    #[Test]
    public function requiere_permiso_asignar_evidencias()
    {
        $userSinPermiso = User::factory()->create();

        $response = $this->actingAs($userSinPermiso, 'sanctum')
            ->postJson('/api/evidencias-asignaciones/validar-duplicados', [
                'proceso_id' => $this->process->proceso_id,
                'evidencia_id' => $this->evidence->evidencia_id,
                'usuarios' => [1, 2, 3],
            ]);

        $response->assertStatus(403);
    }

    #[Test]
    public function valida_campos_requeridos()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/evidencias-asignaciones/validar-duplicados', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['proceso_id', 'evidencia_id', 'usuarios']);
    }

    #[Test]
    public function valida_que_proceso_exista()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/evidencias-asignaciones/validar-duplicados', [
                'proceso_id' => 99999,
                'evidencia_id' => $this->evidence->evidencia_id,
                'usuarios' => [1, 2, 3],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['proceso_id']);
    }

    #[Test]
    public function valida_que_evidencia_exista()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/evidencias-asignaciones/validar-duplicados', [
                'proceso_id' => $this->process->proceso_id,
                'evidencia_id' => 99999,
                'usuarios' => [1, 2, 3],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['evidencia_id']);
    }

    #[Test]
    public function valida_array_usuarios_minimo_uno()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/evidencias-asignaciones/validar-duplicados', [
                'proceso_id' => $this->process->proceso_id,
                'evidencia_id' => $this->evidence->evidencia_id,
                'usuarios' => [],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['usuarios']);
    }

    #[Test]
    public function valida_que_usuarios_existan()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/evidencias-asignaciones/validar-duplicados', [
                'proceso_id' => $this->process->proceso_id,
                'evidencia_id' => $this->evidence->evidencia_id,
                'usuarios' => [99999, 88888],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['usuarios.0', 'usuarios.1']);
    }

    #[Test]
    public function solo_detecta_duplicados_para_mismo_proceso_y_evidencia()
    {
        $otroProceso = Process::factory()->create();
        $otraEvidencia = Evidence::factory()->create();

        // Crear asignación en otro proceso
        EvidenceAssignment::create([
            'proceso_id' => $otroProceso->proceso_id,
            'evidencia_id' => $this->evidence->evidencia_id,
            'usuario_id' => $this->usuarios[0]->usuario_id,
            'estado' => 'pendiente',
            'fecha_asignacion' => now(),
        ]);

        // Crear asignación en otra evidencia
        EvidenceAssignment::create([
            'proceso_id' => $this->process->proceso_id,
            'evidencia_id' => $otraEvidencia->evidencia_id,
            'usuario_id' => $this->usuarios[1]->usuario_id,
            'estado' => 'pendiente',
            'fecha_asignacion' => now(),
        ]);

        // Validar con el proceso y evidencia original
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/evidencias-asignaciones/validar-duplicados', [
                'proceso_id' => $this->process->proceso_id,
                'evidencia_id' => $this->evidence->evidencia_id,
                'usuarios' => $this->usuarios->pluck('usuario_id')->toArray(),
            ]);

        // No debe detectar duplicados porque son de otro proceso/evidencia
        $response->assertStatus(200)
            ->assertJson([
                'tiene_duplicados' => false,
                'total_duplicados' => 0,
            ]);
    }
}
