<?php

use App\Models\ImprovementCommitment;
use App\Models\Process;
use App\Models\AccreditationCycle;
use App\Models\Career;
use App\Models\Campus;
use App\Models\CareerCampus;
use App\Models\Evidence;
use App\Models\EvidenceAssignment;
use App\Models\User;
use App\Models\Criterion;
use App\Models\Component;
use App\Models\Dimension;
use App\Services\ImprovementCommitmentService;
use Illuminate\Validation\ValidationException;

/**
 * Pruebas unitarias para ImprovementCommitmentService.
 * 
 * Valida la lógica de negocio del servicio:
 * - Listar compromisos con filtros y paginación
 * - Obtener compromiso específico
 * - Crear compromiso con validaciones
 * - Actualizar compromiso
 * - Activar/desactivar compromiso
 * - Filtrar por usuario y evidencia
 */

uses()->group('unit');

beforeEach(function() {
    $this->service = new ImprovementCommitmentService();
    $this->user = User::factory()->create();
    $this->process = Process::factory()->create();
});

it('un compromiso pertenece a un proceso', function () {
        $process = Process::factory()->create();
        $commitment = ImprovementCommitment::factory()->create([
            'proceso_id' => $process->proceso_id
        ]);

        $this->assertInstanceOf(Process::class, $commitment->process);
        $this->assertEquals($process->proceso_id, $commitment->process->proceso_id);
    });

it('un compromiso tiene muchas evidencias', function () {
        $commitment = ImprovementCommitment::factory()->create();
        $evidences = Evidence::factory()->count(3)->create();

        $commitment->evidences()->attach($evidences->pluck('evidencia_id')->toArray());

        $this->assertCount(3, $commitment->evidences);
    });

it('un compromiso tiene asignaciones de evidencias', function () {
        $commitment = ImprovementCommitment::factory()->create();
        $assignments = EvidenceAssignment::factory()->count(2)->create();

        $commitment->assignedEvidences()->attach($assignments->pluck('evidencia_asignacion_id')->toArray());

        $this->assertCount(2, $commitment->assignedEvidences);
    });

it('retorna datos paginados', function () {
        ImprovementCommitment::factory()->count(15)->create();

        $result = $this->service->listCommitments(10, []);

        $this->assertEquals(15, $result->total());
        $this->assertEquals(10, $result->perPage());
        $this->assertCount(10, $result->items());
    });

it('filtra por estado', function () {
        ImprovementCommitment::factory()->count(3)->create(['estado' => 'Pendiente']);
        ImprovementCommitment::factory()->count(2)->create(['estado' => 'Completado']);

        $result = $this->service->listCommitments(10, ['estado' => 'Pendiente']);

        $this->assertEquals(3, $result->total());
    });

it('filtra por proceso_id', function () {
        $process = Process::factory()->create();
        ImprovementCommitment::factory()->count(2)->create(['proceso_id' => $process->proceso_id]);
        ImprovementCommitment::factory()->count(3)->create();

        $result = $this->service->listCommitments(10, ['proceso_id' => $process->proceso_id]);

        $this->assertEquals(2, $result->total());
    });

it('filtra por usuario_id', function () {
        $user = User::factory()->create();
        $commitment = ImprovementCommitment::factory()->create();
        $assignment = EvidenceAssignment::factory()->create(['usuario_id' => $user->usuario_id]);
        
        $commitment->assignedEvidences()->attach($assignment->evidencia_asignacion_id);

        ImprovementCommitment::factory()->count(3)->create();

        $result = $this->service->listCommitments(10, ['usuario_id' => $user->usuario_id]);

        $this->assertEquals(1, $result->total());
    });

it('filtra por búsqueda', function () {
        ImprovementCommitment::factory()->create(['descripcion' => 'Mejorar infraestructura']);
        ImprovementCommitment::factory()->create(['descripcion' => 'Actualizar equipos']);
        ImprovementCommitment::factory()->create(['descripcion' => 'Capacitación docente']);

        $result = $this->service->listCommitments(10, ['search' => 'infraestructura']);

        $this->assertEquals(1, $result->total());
    });

it('retorna compromiso existente', function () {
        $commitment = ImprovementCommitment::factory()->create();

        $result = $this->service->getCommitment($commitment->compromiso_mejora_id);

        $this->assertNotNull($result);
        $this->assertEquals($commitment->compromiso_mejora_id, $result->compromiso_mejora_id);
    });

it('retorna null si no existe', function () {
        $result = $this->service->getCommitment(99999);

        $this->assertNull($result);
    });

it('crea compromiso correctamente', function () {
        $process = Process::factory()->create();
        $dimension = Dimension::factory()->create();
        $component = Component::factory()->create(['dimension_id' => $dimension->dimension_id]);
        $criterion = Criterion::factory()->create(['componente_id' => $component->componente_id]);
        $evidence = Evidence::factory()->create(['criterio_id' => $criterion->criterio_id]);

        $data = [
            'proceso_id' => $process->proceso_id,
            'descripcion' => 'Mejorar laboratorios',
            'fecha_inicio' => now()->addDays(1)->format('Y-m-d'),
            'fecha_fin' => now()->addDays(30)->format('Y-m-d'),
            'selecciones' => [
                [
                    'entidad_tipo' => 'EVIDENCIA',
                    'entidad_id' => $evidence->evidencia_id
                ]
            ]
        ];

        $result = $this->service->createCommitment($data);

        $this->assertNotNull($result);
        $this->assertEquals('Mejorar laboratorios', $result->descripcion);
        $this->assertEquals('Pendiente', $result->estado);
        $this->assertDatabaseHas('COMPROMISO_MEJORA', [
            'descripcion' => 'Mejorar laboratorios',
            'estado' => 'Pendiente'
        ]);
    });

it('no permite duplicados por proceso', function () {
        $process = Process::factory()->create();
        $dimension = Dimension::factory()->create();
        $component = Component::factory()->create(['dimension_id' => $dimension->dimension_id]);
        $criterion = Criterion::factory()->create(['componente_id' => $component->componente_id]);
        $evidence = Evidence::factory()->create(['criterio_id' => $criterion->criterio_id]);

        ImprovementCommitment::factory()->create(['proceso_id' => $process->proceso_id]);

        $data = [
            'proceso_id' => $process->proceso_id,
            'descripcion' => 'Otro compromiso',
            'fecha_inicio' => now()->addDays(1)->format('Y-m-d'),
            'fecha_fin' => now()->addDays(30)->format('Y-m-d'),
            'selecciones' => [
                [
                    'entidad_tipo' => 'EVIDENCIA',
                    'entidad_id' => $evidence->evidencia_id
                ]
            ]
        ];

        $result = $this->service->createCommitment($data);

        $this->assertNull($result);
    });

it('vincula evidencias correctamente', function () {
        $process = Process::factory()->create();
        $dimension = Dimension::factory()->create();
        $component = Component::factory()->create(['dimension_id' => $dimension->dimension_id]);
        $criterion = Criterion::factory()->create(['componente_id' => $component->componente_id]);
        $evidence1 = Evidence::factory()->create(['criterio_id' => $criterion->criterio_id]);
        $evidence2 = Evidence::factory()->create(['criterio_id' => $criterion->criterio_id]);

        $data = [
            'proceso_id' => $process->proceso_id,
            'descripcion' => 'Test evidencias',
            'fecha_inicio' => now()->addDays(1)->format('Y-m-d'),
            'fecha_fin' => now()->addDays(30)->format('Y-m-d'),
            'selecciones' => [
                ['entidad_tipo' => 'EVIDENCIA', 'entidad_id' => $evidence1->evidencia_id],
                ['entidad_tipo' => 'EVIDENCIA', 'entidad_id' => $evidence2->evidencia_id]
            ]
        ];

        $result = $this->service->createCommitment($data);

        $this->assertCount(2, $result->evidences);
    });

it('actualiza campos correctamente', function () {
        $commitment = ImprovementCommitment::factory()->create([
            'descripcion' => 'Descripción original',
            'estado' => 'Pendiente'
        ]);

        $data = [
            'descripcion' => 'Descripción actualizada',
            'estado' => 'En Progreso'
        ];

        $result = $this->service->updateCommitment($commitment, $data);

        $this->assertNotNull($result);
        $this->assertEquals('Descripción actualizada', $result->descripcion);
        $this->assertEquals('En Progreso', $result->estado);
    });

it('retorna null sin cambios', function () {
        $commitment = ImprovementCommitment::factory()->create([
            'descripcion' => 'Sin cambios'
        ]);

        $data = [
            'descripcion' => 'Sin cambios'
        ];

        $result = $this->service->updateCommitment($commitment, $data);

        $this->assertNull($result);
    });

it('actualiza evidencias', function () {
        $process = Process::factory()->create();
        $commitment = ImprovementCommitment::factory()->create(['proceso_id' => $process->proceso_id]);
        $dimension = Dimension::factory()->create();
        $component = Component::factory()->create(['dimension_id' => $dimension->dimension_id]);
        $criterion = Criterion::factory()->create(['componente_id' => $component->componente_id]);
        $oldEvidence = Evidence::factory()->create(['criterio_id' => $criterion->criterio_id]);
        $newEvidence = Evidence::factory()->create(['criterio_id' => $criterion->criterio_id]);

        $commitment->evidences()->attach($oldEvidence->evidencia_id);

        $data = [
            'selecciones' => [
                ['entidad_tipo' => 'EVIDENCIA', 'entidad_id' => $newEvidence->evidencia_id]
            ]
        ];

        $result = $this->service->updateCommitment($commitment, $data);

        $this->assertCount(1, $result->evidences);
        $this->assertEquals($newEvidence->evidencia_id, $result->evidences->first()->evidencia_id);
    });

it('activa un compromiso', function () {
        $commitment = ImprovementCommitment::factory()->create(['activo' => false]);

        $result = $this->service->setActive($commitment, true);

        $this->assertTrue($result->activo);
        $this->assertDatabaseHas('COMPROMISO_MEJORA', [
            'compromiso_mejora_id' => $commitment->compromiso_mejora_id,
            'activo' => true
        ]);
    });

it('desactiva un compromiso', function () {
        $commitment = ImprovementCommitment::factory()->create(['activo' => true]);

        $result = $this->service->setActive($commitment, false);

        $this->assertFalse($result->activo);
        $this->assertDatabaseHas('COMPROMISO_MEJORA', [
            'compromiso_mejora_id' => $commitment->compromiso_mejora_id,
            'activo' => false
        ]);
    });

it('retorna compromisos del usuario', function () {
        $user = User::factory()->create();
        $commitment1 = ImprovementCommitment::factory()->create();
        $commitment2 = ImprovementCommitment::factory()->create();
        $assignment = EvidenceAssignment::factory()->create(['usuario_id' => $user->usuario_id]);

        $commitment1->assignedEvidences()->attach($assignment->evidencia_asignacion_id);

        // Crear otro compromiso sin asignaciones a este usuario
        ImprovementCommitment::factory()->create();

        $result = $this->service->getCommitmentsByUser($user->usuario_id);

        $this->assertCount(1, $result);
        $this->assertEquals($commitment1->compromiso_mejora_id, $result->first()->compromiso_mejora_id);
    });

it('se puede crear un compromiso', function () {
        $process = Process::factory()->create();
        $commitment = ImprovementCommitment::factory()->create([
            'proceso_id' => $process->proceso_id,
        ]);

        $this->assertDatabaseHas('COMPROMISO_MEJORA', [
            'compromiso_mejora_id' => $commitment->compromiso_mejora_id,
        ]);
    });

it('requiere proceso_id', function () {
        ImprovementCommitment::factory()->create(['proceso_id' => null]);
    })->throws(\Illuminate\Database\QueryException::class);

it('se puede actualizar un compromiso', function () {
        $commitment = ImprovementCommitment::factory()->create();
        $newProcess = Process::factory()->create();

        $commitment->update(['proceso_id' => $newProcess->proceso_id]);

        $this->assertDatabaseHas('COMPROMISO_MEJORA', [
            'compromiso_mejora_id' => $commitment->compromiso_mejora_id,
            'proceso_id' => $newProcess->proceso_id
        ]);
    });

it('se puede eliminar un compromiso', function () {
        $commitment = ImprovementCommitment::factory()->create();
        $id = $commitment->compromiso_mejora_id;

        $commitment->delete();

        $this->assertDatabaseMissing('COMPROMISO_MEJORA', [
            'compromiso_mejora_id' => $id
        ]);
    });

it('estado por defecto es Pendiente', function () {
        $process = Process::factory()->create();
        $dimension = Dimension::factory()->create();
        $component = Component::factory()->create(['dimension_id' => $dimension->dimension_id]);
        $criterion = Criterion::factory()->create(['componente_id' => $component->componente_id]);
        $evidence = Evidence::factory()->create(['criterio_id' => $criterion->criterio_id]);

        $data = [
            'proceso_id' => $process->proceso_id,
            'descripcion' => 'Test',
            'fecha_inicio' => now()->addDays(1)->format('Y-m-d'),
            'fecha_fin' => now()->addDays(30)->format('Y-m-d'),
            'selecciones' => [
                ['entidad_tipo' => 'EVIDENCIA', 'entidad_id' => $evidence->evidencia_id]
            ]
        ];

        $result = $this->service->createCommitment($data);

        $this->assertEquals('Pendiente', $result->estado);
    });

it('crea asignaciones a usuarios', function () {
        $process = Process::factory()->create();
        $dimension = Dimension::factory()->create();
        $component = Component::factory()->create(['dimension_id' => $dimension->dimension_id]);
        $criterion = Criterion::factory()->create(['componente_id' => $component->componente_id]);
        $evidence = Evidence::factory()->create(['criterio_id' => $criterion->criterio_id]);
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $data = [
            'proceso_id' => $process->proceso_id,
            'descripcion' => 'Test asignaciones',
            'fecha_inicio' => now()->addDays(1)->format('Y-m-d'),
            'fecha_fin' => now()->addDays(30)->format('Y-m-d'),
            'selecciones' => [
                ['entidad_tipo' => 'EVIDENCIA', 'entidad_id' => $evidence->evidencia_id]
            ],
            'evidencias_asignar' => [
                [
                    'evidencia_id' => $evidence->evidencia_id,
                    'usuarios' => [$user1->usuario_id, $user2->usuario_id],
                    'fecha_limite' => now()->addDays(20)->format('Y-m-d'),
                    'comentario' => 'Asignación de prueba'
                ]
            ]
        ];

        $result = $this->service->createCommitment($data);

        $this->assertNotNull($result);
        
        // Verificar que se crearon las asignaciones
        $this->assertDatabaseHas('EVIDENCIA_ASIGNACION', [
            'evidencia_id' => $evidence->evidencia_id,
            'usuario_id' => $user1->usuario_id,
            'proceso_id' => $process->proceso_id
        ]);
        
        $this->assertDatabaseHas('EVIDENCIA_ASIGNACION', [
            'evidencia_id' => $evidence->evidencia_id,
            'usuario_id' => $user2->usuario_id,
            'proceso_id' => $process->proceso_id
        ]);
        
        // Verificar que las asignaciones están vinculadas al compromiso
        $this->assertGreaterThan(0, $result->assignedEvidences->count());
    });

it('crea asignaciones a roles', function () {
        $profesorRole = \Spatie\Permission\Models\Role::create([
            'name' => 'Profesor Test',
            'guard_name' => 'api'
        ]);

        $process = Process::factory()->create();
        $dimension = Dimension::factory()->create();
        $component = Component::factory()->create(['dimension_id' => $dimension->dimension_id]);
        $criterion = Criterion::factory()->create(['componente_id' => $component->componente_id]);
        $evidence = Evidence::factory()->create(['criterio_id' => $criterion->criterio_id]);
        
        // Crear usuarios con rol
        $profesor1 = User::factory()->create(['status' => 'active']);
        $profesor2 = User::factory()->create(['status' => 'active']);
        $profesor1->assignRole($profesorRole);
        $profesor2->assignRole($profesorRole);

        $data = [
            'proceso_id' => $process->proceso_id,
            'descripcion' => 'Test asignaciones por rol',
            'fecha_inicio' => now()->addDays(1)->format('Y-m-d'),
            'fecha_fin' => now()->addDays(30)->format('Y-m-d'),
            'selecciones' => [
                ['entidad_tipo' => 'EVIDENCIA', 'entidad_id' => $evidence->evidencia_id]
            ],
            'evidencias_asignar' => [
                [
                    'evidencia_id' => $evidence->evidencia_id,
                    'roles' => [$profesorRole->id],
                    'fecha_limite' => now()->addDays(20)->format('Y-m-d')
                ]
            ]
        ];

        $result = $this->service->createCommitment($data);

        $this->assertNotNull($result);
        
        // Verificar que se asignó a ambos profesores
        $this->assertDatabaseHas('EVIDENCIA_ASIGNACION', [
            'evidencia_id' => $evidence->evidencia_id,
            'usuario_id' => $profesor1->usuario_id
        ]);
        
        $this->assertDatabaseHas('EVIDENCIA_ASIGNACION', [
            'evidencia_id' => $evidence->evidencia_id,
            'usuario_id' => $profesor2->usuario_id
        ]);
    });

it('no permite asignar evidencia no vinculada', function () {
        $process = Process::factory()->create();
        $dimension = Dimension::factory()->create();
        $component = Component::factory()->create(['dimension_id' => $dimension->dimension_id]);
        $criterion = Criterion::factory()->create(['componente_id' => $component->componente_id]);
        $evidence1 = Evidence::factory()->create(['criterio_id' => $criterion->criterio_id]);
        $evidence2 = Evidence::factory()->create(['criterio_id' => $criterion->criterio_id]);
        $user = User::factory()->create();

        $data = [
            'proceso_id' => $process->proceso_id,
            'descripcion' => 'Test validación',
            'fecha_inicio' => now()->addDays(1)->format('Y-m-d'),
            'fecha_fin' => now()->addDays(30)->format('Y-m-d'),
            'selecciones' => [
                ['entidad_tipo' => 'EVIDENCIA', 'entidad_id' => $evidence1->evidencia_id]
            ],
            'evidencias_asignar' => [
                [
                    'evidencia_id' => $evidence2->evidencia_id, // NO está en selecciones
                    'usuarios' => [$user->usuario_id]
                ]
            ]
        ];

        $this->service->createCommitment($data);
    })->throws(ValidationException::class);

it('no permite crear asignación duplicada', function () {
        $process = Process::factory()->create();
        $dimension = Dimension::factory()->create();
        $component = Component::factory()->create(['dimension_id' => $dimension->dimension_id]);
        $criterion = Criterion::factory()->create(['componente_id' => $component->componente_id]);
        $evidence = Evidence::factory()->create(['criterio_id' => $criterion->criterio_id]);
        $user = User::factory()->create();

        // Crear asignación existente
        EvidenceAssignment::factory()->create([
            'proceso_id' => $process->proceso_id,
            'evidencia_id' => $evidence->evidencia_id,
            'usuario_id' => $user->usuario_id
        ]);

        $data = [
            'proceso_id' => $process->proceso_id,
            'descripcion' => 'Test duplicado',
            'fecha_inicio' => now()->addDays(1)->format('Y-m-d'),
            'fecha_fin' => now()->addDays(30)->format('Y-m-d'),
            'selecciones' => [
                ['entidad_tipo' => 'EVIDENCIA', 'entidad_id' => $evidence->evidencia_id]
            ],
            'evidencias_asignar' => [
                [
                    'evidencia_id' => $evidence->evidencia_id,
                    'usuarios' => [$user->usuario_id] // Ya existe
                ]
            ]
        ];

        $this->service->createCommitment($data);
    })->throws(ValidationException::class);

it('sincroniza asignaciones', function () {
        $commitment = ImprovementCommitment::factory()->create([
            'estado' => 'Pendiente'
        ]);
        $dimension = Dimension::factory()->create();
        $component = Component::factory()->create(['dimension_id' => $dimension->dimension_id]);
        $criterion = Criterion::factory()->create(['componente_id' => $component->componente_id]);
        $evidence = Evidence::factory()->create(['criterio_id' => $criterion->criterio_id]);
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        // Vincular evidencia al compromiso
        $commitment->evidences()->attach($evidence->evidencia_id);

        // Crear asignación inicial
        $oldAssignment = EvidenceAssignment::factory()->create([
            'proceso_id' => $commitment->proceso_id,
            'evidencia_id' => $evidence->evidencia_id,
            'usuario_id' => $user1->usuario_id
        ]);
        $commitment->assignedEvidences()->attach($oldAssignment->evidencia_asignacion_id);

        $data = [
            'estado' => 'En Progreso', // Cambiar estado para detectar cambios
            'evidencias_asignar' => [
                [
                    'evidencia_id' => $evidence->evidencia_id,
                    'usuarios' => [$user2->usuario_id], // Diferente usuario
                    'fecha_limite' => now()->addDays(15)->format('Y-m-d')
                ]
            ]
        ];

        $result = $this->service->updateCommitment($commitment, $data);

        $this->assertNotNull($result);
        $this->assertEquals('En Progreso', $result->estado);
        
        // Verificar que se creó la nueva asignación
        $this->assertDatabaseHas('EVIDENCIA_ASIGNACION', [
            'evidencia_id' => $evidence->evidencia_id,
            'usuario_id' => $user2->usuario_id
        ]);
    });

it('guarda comentario en tabla pivot', function () {
        $process = Process::factory()->create();
        $dimension = Dimension::factory()->create();
        $component = Component::factory()->create(['dimension_id' => $dimension->dimension_id]);
        $criterion = Criterion::factory()->create(['componente_id' => $component->componente_id]);
        $evidence = Evidence::factory()->create(['criterio_id' => $criterion->criterio_id]);
        $user = User::factory()->create();

        $data = [
            'proceso_id' => $process->proceso_id,
            'descripcion' => 'Test comentario',
            'fecha_inicio' => now()->addDays(1)->format('Y-m-d'),
            'fecha_fin' => now()->addDays(30)->format('Y-m-d'),
            'selecciones' => [
                ['entidad_tipo' => 'EVIDENCIA', 'entidad_id' => $evidence->evidencia_id]
            ],
            'evidencias_asignar' => [
                [
                    'evidencia_id' => $evidence->evidencia_id,
                    'usuarios' => [$user->usuario_id],
                    'comentario' => 'Este es un comentario de prueba'
                ]
            ]
        ];

        $result = $this->service->createCommitment($data);

        $this->assertNotNull($result);
        
        // Verificar que el comentario esté en la tabla pivot
        $this->assertDatabaseHas('COMPROMISO_MEJORA_EVIDENCIA_ASIGNACION', [
            'compromiso_mejora_id' => $result->compromiso_mejora_id,
            'comentario' => 'Este es un comentario de prueba'
        ]);
    });
