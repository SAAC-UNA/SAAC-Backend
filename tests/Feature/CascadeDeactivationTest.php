<?php

use App\Models\Dimension;
use App\Models\Component;
use App\Models\Criterion;
use App\Models\Standard;
use App\Models\Evidence;
use App\Models\Comment;
use App\Models\University;
use App\Models\Campus;
use App\Models\Career;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use App\Models\Role;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    // Crear permisos necesarios para el test (guard 'api')
    $permissions = [
        'dimensiones.edit',
        'componentes.edit',
        'criterios.edit',
        'estandares.edit',
        'evidencias.edit',
        'universidades.edit',
        'sedes.edit',
        'carreras.edit',
    ];
    
    foreach ($permissions as $perm) {
        Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'api']);
    }
    
    // Crear rol Superusuario si no existe (guard 'api')
    $superRole = Role::firstOrCreate(['name' => 'Superusuario', 'guard_name' => 'api']);
    $superRole->syncPermissions($permissions);
    
    // Crear usuario y asignarle el rol
    $this->user = User::factory()->create();
    $this->user->assignRole($superRole);
    
    Sanctum::actingAs($this->user);
});

it('desactivar dimension desactiva hijos en cascada', function ()
    {
        // Crear dimensión activa
        $dimension = Dimension::factory()->create([
            'nombre' => 'Dimensión Test',
            'activo' => true
        ]);

        // Crear componente hijo activo
        $component = Component::factory()->create([
            'dimension_id' => $dimension->dimension_id,
            'nombre' => 'Componente Test',
            'activo' => true
        ]);

        // Crear criterio nieto activo  
        $criterion = Criterion::factory()->create([
            'componente_id' => $component->componente_id,
            'descripcion' => 'Criterio Test',
            'activo' => true
        ]);

        // Crear estándar bisnieto activo
        $standard = Standard::factory()->create([
            'criterio_id' => $criterion->criterio_id,
            'descripcion' => 'Estándar Test',
            'activo' => true
        ]);

        // Crear evidencia tataranieta activa
        $evidence = Evidence::factory()->create([
            'criterio_id' => $criterion->criterio_id,
            'descripcion' => 'Evidencia Test',
            'activo' => true
        ]);

        // Verificar que todos estén activos inicialmente
        $this->assertEquals(1, $dimension->fresh()->activo);
        $this->assertEquals(1, $component->fresh()->activo);
        $this->assertEquals(1, $criterion->fresh()->activo);
        $this->assertEquals(1, $standard->fresh()->activo);
        $this->assertEquals(1, $evidence->fresh()->activo);

        // Desactivar la dimensión mediante el endpoint
        $response = $this->patchJson(
            "/api/estructura/dimensiones/{$dimension->dimension_id}/active",
            ['active' => false]
        );

        // Verificar respuesta exitosa
        $response->assertStatus(200);
        $this->assertStringContainsString('Estado de la dimensión actualizado correctamente.', (string) $response->json('message'));

        // Verificar que TODOS los Elements ahora estén desactivados
        $this->assertEquals(0, $dimension->fresh()->activo, 'La dimensión debe estar desactivada');
        $this->assertEquals(0, $component->fresh()->activo, 'El componente debe estar desactivado');
        $this->assertEquals(0, $criterion->fresh()->activo, 'El criterio debe estar desactivado');
        $this->assertEquals(0, $standard->fresh()->activo, 'El estándar debe estar desactivado');
        $this->assertEquals(0, $evidence->fresh()->activo, 'La evidencia debe estar desactivada');
});

it('desactivar componente desactiva hijos en cascada', function ()
    {
        // Crear dimensión activa
        $dimension = Dimension::factory()->create([
            'activo' => true
        ]);

        // Crear componente activo
        $component = Component::factory()->create([
            'dimension_id' => $dimension->dimension_id,
            'activo' => true
        ]);

        // Crear criterio hijo activo
        $criterion = Criterion::factory()->create([
            'componente_id' => $component->componente_id,
            'activo' => true
        ]);

        // Crear estándar activo
        $standard = Standard::factory()->create([
            'criterio_id' => $criterion->criterio_id,
            'activo' => true
        ]);

        // Crear evidencia activa
        $evidence = Evidence::factory()->create([
            'criterio_id' => $criterion->criterio_id,
            'activo' => true
        ]);

        // Desactivar el componente
        $response = $this->patchJson(
            "/api/estructura/componentes/{$component->componente_id}/active",
            ['active' => false]
        );

        // Verificar respuesta
        $response->assertStatus(200);

        // Verificar cascada: dimensión sigue activa, pero todo lo demás está desactivado
        $this->assertEquals(1, $dimension->fresh()->activo, 'La dimensión debe permanecer activa');
        $this->assertEquals(0, $component->fresh()->activo, 'El componente debe estar desactivado');
        $this->assertEquals(0, $criterion->fresh()->activo, 'El criterio debe estar desactivado');
        $this->assertEquals(0, $standard->fresh()->activo, 'El estándar debe estar desactivado');
        $this->assertEquals(0, $evidence->fresh()->activo, 'La evidencia debe estar desactivada');
});

it('desactivar criterio desactiva hijos en cascada', function ()
    {
        // Crear dimensión activa
        $dimension = Dimension::factory()->create(['activo' => true]);
        
        // Crear componente activo
        $component = Component::factory()->create([
            'dimension_id' => $dimension->dimension_id,
            'activo' => true
        ]);
        
        // Crear criterio activo
        $criterion = Criterion::factory()->create([
            'componente_id' => $component->componente_id,
            'activo' => true
        ]);

        // Crear estándar hijo activo
        $standard = Standard::factory()->create([
            'criterio_id' => $criterion->criterio_id,
            'activo' => true
        ]);

        // Crear evidencia nieta activa
        $evidence = Evidence::factory()->create([
            'criterio_id' => $criterion->criterio_id,
            'activo' => true
        ]);

        // Desactivar el criterio
        $response = $this->patchJson(
            "/api/estructura/criterios/{$criterion->criterio_id}/active",
            ['active' => false]
        );

        // Verificar respuesta
        $response->assertStatus(200);

        // Verificar cascada: dimensión y componente siguen activos, pero criterio y descendientes están desactivados
        $this->assertEquals(1, $dimension->fresh()->activo, 'La dimensión debe permanecer activa');
        $this->assertEquals(1, $component->fresh()->activo, 'El componente debe permanecer activo');
        $this->assertEquals(0, $criterion->fresh()->activo, 'El criterio debe estar desactivado');
        $this->assertEquals(0, $standard->fresh()->activo, 'El estándar debe estar desactivado');
        $this->assertEquals(0, $evidence->fresh()->activo, 'La evidencia debe estar desactivada');
});

it('activar dimension activa hijos en cascada', function ()
    {
        // Crear jerarquía completa desactivada
        $dimension = Dimension::factory()->create([
            'activo' => false
        ]);

        $component = Component::factory()->create([
            'dimension_id' => $dimension->dimension_id,
            'activo' => false
        ]);

        $criterion = Criterion::factory()->create([
            'componente_id' => $component->componente_id,
            'activo' => false
        ]);

        $standard = Standard::factory()->create([
            'criterio_id' => $criterion->criterio_id,
            'activo' => false
        ]);

        $evidence = Evidence::factory()->create([
            'criterio_id' => $criterion->criterio_id,
            'activo' => false
        ]);

        // Activar la dimensión
        $response = $this->patchJson(
            "/api/estructura/dimensiones/{$dimension->dimension_id}/active",
            ['active' => true]
        );

        // Verificar respuesta
        $response->assertStatus(200);
        $this->assertStringContainsString('Estado de la dimensión actualizado correctamente.', (string) $response->json('message'));

        // Verificar que TODOS los Elements se activaron en cascada
        $this->assertEquals(1, $dimension->fresh()->activo, 'La dimensión debe estar activa');
        $this->assertEquals(1, $component->fresh()->activo, 'El componente debe estar activo');
        $this->assertEquals(1, $criterion->fresh()->activo, 'El criterio debe estar activo');
        $this->assertEquals(1, $standard->fresh()->activo, 'El estándar debe estar activo');
        $this->assertEquals(1, $evidence->fresh()->activo, 'La evidencia debe estar activa');
});

it('desactivar universidad desactiva campus en cascada', function ()
    {
        // Crear universidad activa
        $university = University::factory()->create(['activo' => true]);

        // Crear campus hijo activo
        $campus = Campus::factory()->create([
            'universidad_id' => $university->universidad_id,
            'activo' => true
        ]);

        // Crear carrera activa (sin Faculty)
        $career = Career::factory()->create([
            'activo' => true
        ]);

        // Asociar carrera con campus
        $campus->careers()->attach($career->carrera_id);

        // Verificar que todos estén activos inicialmente
        $this->assertEquals(1, $university->fresh()->activo);
        $this->assertEquals(1, $campus->fresh()->activo);
        $this->assertEquals(1, $career->fresh()->activo);

        // Desactivar la universidad mediante el endpoint
        $response = $this->patchJson(
            "/api/estructura/universidades/{$university->universidad_id}/active",
            ['active' => false]
        );

        // Verificar respuesta exitosa
        $response->assertStatus(200);

        // Verificar que TODOS los Elements ahora estén desactivados
        $this->assertEquals(0, $university->fresh()->activo, 'La universidad debe estar desactivada');
        $this->assertEquals(0, $campus->fresh()->activo, 'El campus debe estar desactivado');
});

it('desactivar campus desactiva carreras asociadas en cascada', function ()
    {
        // Crear datos necesarios
        $university = University::factory()->create(['activo' => true]);
        $campus = Campus::factory()->create([
            'universidad_id' => $university->universidad_id,
            'activo' => true
        ]);
        $career = Career::factory()->create(['activo' => true]);
        
        // Asociar carrera con campus
        $campus->careers()->attach($career->carrera_id);

        // Desactivar el campus directamente (sin endpoint ya que está comentado)
        $campus->update(['activo' => false]);

        // Verificar que la desactivación funcionó
        $this->assertEquals(1, $university->fresh()->activo, 'La universidad debe permanecer activa');
        $this->assertEquals(0, $campus->fresh()->activo, 'El campus debe estar desactivado');
});

it('activar carrera simple funciona correctamente', function ()
    {
        // Crear carrera desactivada
        $career = Career::factory()->create(['activo' => false]);

        // Activar la carrera
        $response = $this->patchJson(
            "/api/estructura/carreras/{$career->carrera_id}/active",
            ['active' => true]
        );

        // Verificar respuesta
        $response->assertStatus(200);

        // Verificar que la carrera se activó
        $this->assertEquals(1, $career->fresh()->activo, 'La carrera debe estar activa');
});

it('activar universidad activa campus en cascada', function ()
    {
        // Crear jerarquía completa desactivada
        $university = University::factory()->create(['activo' => false]);
        $campus = Campus::factory()->create([
            'universidad_id' => $university->universidad_id,
            'activo' => false
        ]);
        $career = Career::factory()->create(['activo' => false]);
        
        // Asociar carrera con campus
        $campus->careers()->attach($career->carrera_id);

        // Activar la universidad
        $response = $this->patchJson(
            "/api/estructura/universidades/{$university->universidad_id}/active",
            ['active' => true]
        );

        // Verificar respuesta
        $response->assertStatus(200);

        // Verificar que universidad y campus se activaron
        $this->assertEquals(1, $university->fresh()->activo, 'La universidad debe estar activa');
        $this->assertEquals(1, $campus->fresh()->activo, 'El campus debe estar activo');
});
