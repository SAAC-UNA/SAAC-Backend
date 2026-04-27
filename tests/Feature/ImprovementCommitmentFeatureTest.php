<?php

use App\Models\ImprovementCommitment;
use App\Models\User;
use App\Models\Process;
use App\Models\AccreditationCycle;
use App\Models\Career;
use App\Models\Campus;
use App\Models\CareerCampus;
use App\Models\Evidence;
use App\Models\Criterion;
use App\Models\Component;
use App\Models\Dimension;
use App\Models\EvidenceAssignment;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

    $this->adminRole = Role::where('name', 'Encargado de Acreditación')->where('guard_name', 'api')->first();
    $this->profesorRole = Role::where('name', 'Profesor')->where('guard_name', 'api')->first();
    $superusuarioRole = Role::where('name', 'Superusuario')->where('guard_name', 'api')->first();

    // Crear usuario autenticado
    $this->user = User::factory()->create();
    $this->user->assignRole($superusuarioRole);
});

it('puede listar compromisos de mejora con paginacion', function () {
        Sanctum::actingAs($this->user);

        ImprovementCommitment::factory()->count(15)->create();

        $response = $this->getJson('/api/compromisos-de-mejora?per_page=10');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'compromiso_mejora_id',
                        'proceso_id',
                        'descripcion',
                        'estado',
                        'fecha_inicio',
                        'fecha_fin'
                    ]
                ],
                'links',
                'meta'
            ])
            ->assertJsonCount(10, 'data');
});
it('puede_filtrar_compromisos_por_estado', function () {
        Sanctum::actingAs($this->user);

        ImprovementCommitment::factory()->count(3)->create(['estado' => 'Pendiente']);
        ImprovementCommitment::factory()->count(2)->create(['estado' => 'En Progreso']);

        $response = $this->getJson('/api/compromisos-de-mejora?estado=Pendiente');

        $response->assertStatus(200);
        $this->assertEquals(3, count($response->json('data')));
});
it('puede_filtrar_compromisos_por_proceso', function () {
        Sanctum::actingAs($this->user);

    $process = Process::factory()->create(['tipo_proceso' => 'Compromiso de mejora']);
        ImprovementCommitment::factory()->count(2)->create(['proceso_id' => $process->proceso_id]);
        ImprovementCommitment::factory()->count(3)->create();

        $response = $this->getJson("/api/compromisos-de-mejora?proceso_id={$process->proceso_id}");

        $response->assertStatus(200);
        $this->assertEquals(2, count($response->json('data')));
});
it('puede_buscar_compromisos_por_descripcion', function () {
        Sanctum::actingAs($this->user);

        ImprovementCommitment::factory()->create(['descripcion' => 'Mejora en infraestructura']);
        ImprovementCommitment::factory()->create(['descripcion' => 'Actualización de equipos']);
        ImprovementCommitment::factory()->create(['descripcion' => 'Capacitación docente']);

        $response = $this->getJson('/api/compromisos-de-mejora?search=infraestructura');

        $response->assertStatus(200);
        $this->assertEquals(1, count($response->json('data')));
        $this->assertStringContainsString('infraestructura', $response->json('data.0.descripcion'));
});
it('puede_ver_compromiso_individual', function () {
        Sanctum::actingAs($this->user);

        $commitment = ImprovementCommitment::factory()->create();

        $response = $this->getJson("/api/compromisos-de-mejora/{$commitment->compromiso_mejora_id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'compromiso_mejora_id',
                    'proceso_id',
                    'descripcion',
                    'estado',
                    'fecha_inicio',
                    'fecha_fin'
                ]
            ])
            ->assertJson([
                'data' => [
                    'compromiso_mejora_id' => $commitment->compromiso_mejora_id
                ]
            ]);
});
it('puede_crear_compromiso_de_mejora', function () {
        Sanctum::actingAs($this->user);

        $career = Career::factory()->create();
        $campus = Campus::factory()->create();
        $careerCampus = CareerCampus::factory()->create([
            'carrera_id' => $career->carrera_id,
            'sede_id' => $campus->sede_id,
        ]);
        $cycle = AccreditationCycle::factory()->create([
            'carrera_sede_id' => $careerCampus->carrera_sede_id,
        ]);
        $process = Process::factory()->create([
            'ciclo_acreditacion_id' => $cycle->ciclo_acreditacion_id,
            'tipo_proceso' => 'Compromiso de mejora',
        ]);
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

        $response = $this->postJson('/api/compromisos-de-mejora', $data);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'compromiso_mejora_id',
                    'descripcion'
                ]
            ]);

        $this->assertDatabaseHas('COMPROMISO_MEJORA', [
            'descripcion' => 'Mejorar laboratorios',
            'estado' => 'Pendiente'
        ]);
});
it('no_permite_duplicar_compromiso_por_proceso', function () {
        Sanctum::actingAs($this->user);

        $career = Career::factory()->create();
        $campus = Campus::factory()->create();
        $careerCampus = CareerCampus::factory()->create([
            'carrera_id' => $career->carrera_id,
            'sede_id' => $campus->sede_id,
        ]);
        $cycle = AccreditationCycle::factory()->create([
            'carrera_sede_id' => $careerCampus->carrera_sede_id,
        ]);
        $process = Process::factory()->create([
            'ciclo_acreditacion_id' => $cycle->ciclo_acreditacion_id,
            'tipo_proceso' => 'Compromiso de mejora',
        ]);
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

        $response = $this->postJson('/api/compromisos-de-mejora', $data);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'message',
                'errors' => ['ciclo_acreditacion_id']
            ]);
});
it('valida_campos_requeridos_al_crear', function () {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/compromisos-de-mejora', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'descripcion',
                'fecha_inicio',
                'fecha_fin',
                'selecciones'
            ]);
});
it('valida_formato_fecha_inicio', function () {
        Sanctum::actingAs($this->user);

    $process = Process::factory()->create(['tipo_proceso' => 'Compromiso de mejora']);
        $dimension = Dimension::factory()->create();
        $component = Component::factory()->create(['dimension_id' => $dimension->dimension_id]);
        $criterion = Criterion::factory()->create(['componente_id' => $component->componente_id]);
        $evidence = Evidence::factory()->create(['criterio_id' => $criterion->criterio_id]);

        $data = [
            'proceso_id' => $process->proceso_id,
            'descripcion' => 'Test',
            'fecha_inicio' => 'fecha-invalida',
            'fecha_fin' => now()->addDays(30)->format('Y-m-d'),
            'selecciones' => [
                ['entidad_tipo' => 'EVIDENCIA', 'entidad_id' => $evidence->evidencia_id]
            ]
        ];

        $response = $this->postJson('/api/compromisos-de-mejora', $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['fecha_inicio']);
});
it('valida_fecha_fin_posterior_a_fecha_inicio', function () {
        Sanctum::actingAs($this->user);

    $process = Process::factory()->create(['tipo_proceso' => 'Compromiso de mejora']);
        $dimension = Dimension::factory()->create();
        $component = Component::factory()->create(['dimension_id' => $dimension->dimension_id]);
        $criterion = Criterion::factory()->create(['componente_id' => $component->componente_id]);
        $evidence = Evidence::factory()->create(['criterio_id' => $criterion->criterio_id]);

        $data = [
            'proceso_id' => $process->proceso_id,
            'descripcion' => 'Test',
            'fecha_inicio' => now()->addDays(10)->format('Y-m-d'),
            'fecha_fin' => now()->addDays(5)->format('Y-m-d'),
            'selecciones' => [
                ['entidad_tipo' => 'EVIDENCIA', 'entidad_id' => $evidence->evidencia_id]
            ]
        ];

        $response = $this->postJson('/api/compromisos-de-mejora', $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['fecha_fin']);
});
it('puede_actualizar_compromiso', function () {
        Sanctum::actingAs($this->user);

        $commitment = ImprovementCommitment::factory()->create([
            'descripcion' => 'Descripción original',
            'estado' => 'Pendiente'
        ]);

        $data = [
            'descripcion' => 'Descripción actualizada',
            'estado' => 'En Progreso'
        ];

        $response = $this->putJson("/api/compromisos-de-mejora/{$commitment->compromiso_mejora_id}", $data);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Compromiso de mejora actualizado con éxito.'
            ]);

        $this->assertDatabaseHas('COMPROMISO_MEJORA', [
            'compromiso_mejora_id' => $commitment->compromiso_mejora_id,
            'descripcion' => 'Descripción actualizada',
            'estado' => 'En Progreso'
        ]);
});
it('no_actualiza_si_no_hay_cambios', function () {
        Sanctum::actingAs($this->user);

        $commitment = ImprovementCommitment::factory()->create([
            'descripcion' => 'Sin cambios'
        ]);

        $data = [
            'descripcion' => 'Sin cambios'
        ];

        $response = $this->putJson("/api/compromisos-de-mejora/{$commitment->compromiso_mejora_id}", $data);

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'Solicitud válida, pero no se aplicaron cambios.'
            ]);
});
it('puede_activar_compromiso', function () {
        Sanctum::actingAs($this->user);

        $commitment = ImprovementCommitment::factory()->create(['activo' => false]);

        $response = $this->patchJson(
            "/api/compromisos-de-mejora/{$commitment->compromiso_mejora_id}/active",
            ['activo' => true]
        );

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Compromiso de mejora activado con éxito.'
            ]);

        $this->assertDatabaseHas('COMPROMISO_MEJORA', [
            'compromiso_mejora_id' => $commitment->compromiso_mejora_id,
            'activo' => true
        ]);
});
it('puede_desactivar_compromiso', function () {
        Sanctum::actingAs($this->user);

        $commitment = ImprovementCommitment::factory()->create(['activo' => true]);

        $response = $this->patchJson(
            "/api/compromisos-de-mejora/{$commitment->compromiso_mejora_id}/active",
            ['activo' => false]
        );

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Compromiso de mejora desactivado con éxito.'
            ]);

        $this->assertDatabaseHas('COMPROMISO_MEJORA', [
            'compromiso_mejora_id' => $commitment->compromiso_mejora_id,
            'activo' => false
        ]);
});
it('valida_campo_activo_requerido', function () {
        Sanctum::actingAs($this->user);

        $commitment = ImprovementCommitment::factory()->create();

        $response = $this->patchJson(
            "/api/compromisos-de-mejora/{$commitment->compromiso_mejora_id}/active",
            []
        );

        $response->assertStatus(422)
            ->assertJsonStructure(['error', 'errors' => ['activo']]);
});
it('puede_filtrar_compromisos_por_usuario', function () {
        Sanctum::actingAs($this->user);

        $user = User::factory()->create();
        $commitment = ImprovementCommitment::factory()->create();
        $evidence = Evidence::factory()->create();
        $assignment = EvidenceAssignment::factory()->create([
            'evidencia_id' => $evidence->evidencia_id,
            'usuario_id' => $user->usuario_id
        ]);

        $commitment->assignedEvidences()->attach($assignment->evidencia_asignacion_id);

        // Crear otros compromisos sin asignaciones a este usuario
        ImprovementCommitment::factory()->count(2)->create();

        $response = $this->getJson("/api/compromisos-de-mejora/usuario/{$user->usuario_id}");

        $response->assertStatus(200)
            ->assertJsonStructure(['data'])
            ->assertJsonCount(1, 'data');
});
it('puede_crear_compromiso_con_asignaciones_a_usuarios', function () {
        Sanctum::actingAs($this->user);

    $process = Process::factory()->create(['tipo_proceso' => 'Compromiso de mejora']);
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

        $response = $this->postJson('/api/compromisos-de-mejora', $data);

        $response->assertStatus(201);

        // Verificar que se crearon las asignaciones
        $this->assertDatabaseHas('EVIDENCIA_ASIGNACION', [
            'evidencia_id' => $evidence->evidencia_id,
            'usuario_id' => $user1->usuario_id,
            'estado' => 'Pendiente'
        ]);

        $this->assertDatabaseHas('EVIDENCIA_ASIGNACION', [
            'evidencia_id' => $evidence->evidencia_id,
            'usuario_id' => $user2->usuario_id,
            'estado' => 'Pendiente'
        ]);
});
it('puede_crear_compromiso_con_asignaciones_a_roles', function () {
        Sanctum::actingAs($this->user);

    $process = Process::factory()->create(['tipo_proceso' => 'Compromiso de mejora']);
        $dimension = Dimension::factory()->create();
        $component = Component::factory()->create(['dimension_id' => $dimension->dimension_id]);
        $criterion = Criterion::factory()->create(['componente_id' => $component->componente_id]);
        $evidence = Evidence::factory()->create(['criterio_id' => $criterion->criterio_id]);

        // Crear usuarios con rol Profesor
        $profesor1 = User::factory()->create(['status' => 'active']);
        $profesor2 = User::factory()->create(['status' => 'active']);
        $profesor1->assignRole($this->profesorRole);
        $profesor2->assignRole($this->profesorRole);

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
                    'roles' => [$this->profesorRole->id],
                    'fecha_limite' => now()->addDays(20)->format('Y-m-d')
                ]
            ]
        ];

        $response = $this->postJson('/api/compromisos-de-mejora', $data);

        $response->assertStatus(201);

        // Verificar que se asignó a todos los profesores activos
        $this->assertDatabaseHas('EVIDENCIA_ASIGNACION', [
            'evidencia_id' => $evidence->evidencia_id,
            'usuario_id' => $profesor1->usuario_id
        ]);

        $this->assertDatabaseHas('EVIDENCIA_ASIGNACION', [
            'evidencia_id' => $evidence->evidencia_id,
            'usuario_id' => $profesor2->usuario_id
        ]);
});
it('no_permite_asignar_evidencia_no_vinculada_al_compromiso', function () {
        Sanctum::actingAs($this->user);

    $process = Process::factory()->create(['tipo_proceso' => 'Compromiso de mejora']);
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
                    'evidencia_id' => $evidence2->evidencia_id, // Esta NO está en selecciones
                    'usuarios' => [$user->usuario_id]
                ]
            ]
        ];

        $response = $this->postJson('/api/compromisos-de-mejora', $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['evidencias_asignar']);
});
it('no_permite_asignacion_duplicada', function () {
        Sanctum::actingAs($this->user);

    $process = Process::factory()->create(['tipo_proceso' => 'Compromiso de mejora']);
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
                    'usuarios' => [$user->usuario_id] // Ya existe esta asignación
                ]
            ]
        ];

        $response = $this->postJson('/api/compromisos-de-mejora', $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['evidencias_asignar']);
});
it('puede_actualizar_compromiso_con_nuevas_asignaciones', function () {
        Sanctum::actingAs($this->user);

        $commitment = ImprovementCommitment::factory()->create([
            'estado' => 'Pendiente'
        ]);
        $dimension = Dimension::factory()->create();
        $component = Component::factory()->create(['dimension_id' => $dimension->dimension_id]);
        $criterion = Criterion::factory()->create(['componente_id' => $component->componente_id]);
        $evidence = Evidence::factory()->create(['criterio_id' => $criterion->criterio_id]);
        $user = User::factory()->create();

        // Vincular evidencia al compromiso
        $commitment->evidences()->attach($evidence->evidencia_id);

        $data = [
            'estado' => 'En Progreso', // Cambiar estado para detectar cambios
            'evidencias_asignar' => [
                [
                    'evidencia_id' => $evidence->evidencia_id,
                    'usuarios' => [$user->usuario_id],
                    'fecha_limite' => now()->addDays(15)->format('Y-m-d')
                ]
            ]
        ];

        $response = $this->putJson("/api/compromisos-de-mejora/{$commitment->compromiso_mejora_id}", $data);

        $response->assertStatus(200);

        $this->assertDatabaseHas('EVIDENCIA_ASIGNACION', [
            'evidencia_id' => $evidence->evidencia_id,
            'usuario_id' => $user->usuario_id
        ]);
});
it('requiere_autenticacion', function () {
        $response = $this->getJson('/api/compromisos-de-mejora');

        $this->assertContains($response->status(), [401, 403]);
});
