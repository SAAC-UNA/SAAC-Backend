<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Dimension;
use App\Models\Component;
use App\Models\Criterion;
use App\Models\Standard;
use App\Models\Evidence;
use App\Models\Comment;
use App\Models\EvidenceState;
use App\Models\University;
use App\Models\Campus;
use App\Models\Faculty;
use App\Models\Career;

class CascadeDeactivationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test que al desactivar una dimensión, se desactivan todos sus elementos hijos en cascada
     * 
     * @test
     */
    public function test_desactivar_dimension_desactiva_hijos_en_cascada()
    {
        // Crear comentarios necesarios
        $comment = Comment::factory()->create();
        $evidenceState = EvidenceState::factory()->create();

        // Crear dimensión activa
        $dimension = Dimension::factory()->create([
            'comentario_id' => $comment->comentario_id,
            'nombre' => 'Dimensión Test',
            'activo' => true
        ]);

        // Crear componente hijo activo
        $component = Component::factory()->create([
            'dimension_id' => $dimension->dimension_id,
            'comentario_id' => $comment->comentario_id,
            'nombre' => 'Componente Test',
            'activo' => true
        ]);

        // Crear criterio nieto activo
        $criterion = Criterion::factory()->create([
            'componente_id' => $component->componente_id,
            'comentario_id' => $comment->comentario_id,
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
            'estado_evidencia_id' => $evidenceState->estado_evidencia_id,
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
        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Estado de la dimensión actualizado correctamente. Elementos hijos desactivados en cascada.'
            ]);

        // Verificar que TODOS los elementos ahora estén desactivados
        $this->assertEquals(0, $dimension->fresh()->activo, 'La dimensión debe estar desactivada');
        $this->assertEquals(0, $component->fresh()->activo, 'El componente debe estar desactivado');
        $this->assertEquals(0, $criterion->fresh()->activo, 'El criterio debe estar desactivado');
        $this->assertEquals(0, $standard->fresh()->activo, 'El estándar debe estar desactivado');
        $this->assertEquals(0, $evidence->fresh()->activo, 'La evidencia debe estar desactivada');
    }

    /**
     * Test que al desactivar un componente, se desactivan criterios, estándares y evidencias
     * 
     * @test
     */
    public function test_desactivar_componente_desactiva_hijos_en_cascada()
    {
        // Crear comentarios y estado necesarios
        $comment = Comment::factory()->create();
        $evidenceState = EvidenceState::factory()->create();

        // Crear dimensión activa
        $dimension = Dimension::factory()->create([
            'comentario_id' => $comment->comentario_id,
            'activo' => true
        ]);

        // Crear componente activo
        $component = Component::factory()->create([
            'dimension_id' => $dimension->dimension_id,
            'comentario_id' => $comment->comentario_id,
            'activo' => true
        ]);

        // Crear criterio activo
        $criterion = Criterion::factory()->create([
            'componente_id' => $component->componente_id,
            'comentario_id' => $comment->comentario_id,
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
            'estado_evidencia_id' => $evidenceState->estado_evidencia_id,
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
    }

    /**
     * Test que al desactivar un criterio, se desactivan estándares y evidencias
     * 
     * @test
     */
    public function test_desactivar_criterio_desactiva_hijos_en_cascada()
    {
        // Crear datos necesarios
        $comment = Comment::factory()->create();
        $evidenceState = EvidenceState::factory()->create();
        $dimension = Dimension::factory()->create(['comentario_id' => $comment->comentario_id, 'activo' => true]);
        $component = Component::factory()->create([
            'dimension_id' => $dimension->dimension_id,
            'comentario_id' => $comment->comentario_id,
            'activo' => true
        ]);

        // Crear criterio activo
        $criterion = Criterion::factory()->create([
            'componente_id' => $component->componente_id,
            'comentario_id' => $comment->comentario_id,
            'activo' => true
        ]);

        // Crear estándar y evidencia activos
        $standard = Standard::factory()->create(['criterio_id' => $criterion->criterio_id, 'activo' => true]);
        $evidence = Evidence::factory()->create([
            'criterio_id' => $criterion->criterio_id,
            'estado_evidencia_id' => $evidenceState->estado_evidencia_id,
            'activo' => true
        ]);

        // Desactivar el criterio
        $response = $this->patchJson(
            "/api/estructura/criterios/{$criterion->criterio_id}/active",
            ['active' => false]
        );

        // Verificar respuesta
        $response->assertStatus(200);

        // Verificar cascada
        $this->assertEquals(1, $dimension->fresh()->activo, 'La dimensión debe permanecer activa');
        $this->assertEquals(1, $component->fresh()->activo, 'El componente debe permanecer activo');
        $this->assertEquals(0, $criterion->fresh()->activo, 'El criterio debe estar desactivado');
        $this->assertEquals(0, $standard->fresh()->activo, 'El estándar debe estar desactivado');
        $this->assertEquals(0, $evidence->fresh()->activo, 'La evidencia debe estar desactivada');
    }

    /**
     * Test que al activar una dimensión SÍ se activan automáticamente todos los hijos
     * 
     * @test
     */
    public function test_activar_dimension_activa_hijos_en_cascada()
    {
        // Crear comentario necesario
        $comment = Comment::factory()->create();
        $evidenceState = EvidenceState::factory()->create();

        // Crear jerarquía completa desactivada
        $dimension = Dimension::factory()->create([
            'comentario_id' => $comment->comentario_id,
            'activo' => false
        ]);

        $component = Component::factory()->create([
            'dimension_id' => $dimension->dimension_id,
            'comentario_id' => $comment->comentario_id,
            'activo' => false
        ]);

        $criterion = Criterion::factory()->create([
            'componente_id' => $component->componente_id,
            'comentario_id' => $comment->comentario_id,
            'activo' => false
        ]);

        $standard = Standard::factory()->create([
            'criterio_id' => $criterion->criterio_id,
            'activo' => false
        ]);

        $evidence = Evidence::factory()->create([
            'criterio_id' => $criterion->criterio_id,
            'estado_evidencia_id' => $evidenceState->estado_evidencia_id,
            'activo' => false
        ]);

        // Activar la dimensión
        $response = $this->patchJson(
            "/api/estructura/dimensiones/{$dimension->dimension_id}/active",
            ['active' => true]
        );

        // Verificar respuesta
        $response->assertStatus(200)
            ->assertJson(['message' => 'Estado de la dimensión actualizado correctamente. Elementos hijos activados en cascada.']);

        // Verificar que TODOS los elementos se activaron en cascada
        $this->assertEquals(1, $dimension->fresh()->activo, 'La dimensión debe estar activa');
        $this->assertEquals(1, $component->fresh()->activo, 'El componente debe estar activo');
        $this->assertEquals(1, $criterion->fresh()->activo, 'El criterio debe estar activo');
        $this->assertEquals(1, $standard->fresh()->activo, 'El estándar debe estar activo');
        $this->assertEquals(1, $evidence->fresh()->activo, 'La evidencia debe estar activa');
    }

    /**
     * Test que al desactivar una universidad, se desactivan campus, facultades y carreras en cascada
     * 
     * @test
     */
    public function test_desactivar_universidad_desactiva_jerarquia_organizacional_en_cascada()
    {
        // Crear universidad activa
        $university = University::factory()->create(['activo' => true]);

        // Crear campus hijo activo
        $campus = Campus::factory()->create([
            'universidad_id' => $university->universidad_id,
            'activo' => true
        ]);

        // Crear facultad activa
        $faculty = Faculty::factory()->create([
            'universidad_id' => $university->universidad_id,
            'sede_id' => $campus->sede_id,
            'activo' => true
        ]);

        // Crear carrera activa
        $career = Career::factory()->create([
            'facultad_id' => $faculty->facultad_id,
            'activo' => true
        ]);

        // Verificar que todos estén activos inicialmente
        $this->assertEquals(1, $university->fresh()->activo);
        $this->assertEquals(1, $campus->fresh()->activo);
        $this->assertEquals(1, $faculty->fresh()->activo);
        $this->assertEquals(1, $career->fresh()->activo);

        // Desactivar la universidad mediante el endpoint
        $response = $this->patchJson(
            "/api/estructura/universidades/{$university->universidad_id}/active",
            ['active' => false]
        );

        // Verificar respuesta exitosa
        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Estado de la universidad actualizado correctamente. Elementos hijos desactivados en cascada.'
            ]);

        // Verificar que TODOS los elementos ahora estén desactivados
        $this->assertEquals(0, $university->fresh()->activo, 'La universidad debe estar desactivada');
        $this->assertEquals(0, $campus->fresh()->activo, 'El campus debe estar desactivado');
        $this->assertEquals(0, $faculty->fresh()->activo, 'La facultad debe estar desactivada');
        $this->assertEquals(0, $career->fresh()->activo, 'La carrera debe estar desactivada');
    }

    /**
     * Test que al desactivar un campus, se desactivan facultades y carreras en cascada
     * 
     * @test
     */
    public function test_desactivar_campus_desactiva_facultades_y_carreras_en_cascada()
    {
        // Crear datos necesarios
        $university = University::factory()->create(['activo' => true]);
        $campus = Campus::factory()->create([
            'universidad_id' => $university->universidad_id,
            'activo' => true
        ]);
        $faculty = Faculty::factory()->create([
            'universidad_id' => $university->universidad_id,
            'sede_id' => $campus->sede_id,
            'activo' => true
        ]);
        $career = Career::factory()->create([
            'facultad_id' => $faculty->facultad_id,
            'activo' => true
        ]);

        // Desactivar el campus
        $response = $this->patchJson(
            "/api/estructura/campuses/{$campus->sede_id}/active",
            ['active' => false]
        );

        // Verificar respuesta
        $response->assertStatus(200);

        // Verificar cascada: universidad sigue activa, pero todo lo demás está desactivado
        $this->assertEquals(1, $university->fresh()->activo, 'La universidad debe permanecer activa');
        $this->assertEquals(0, $campus->fresh()->activo, 'El campus debe estar desactivado');
        $this->assertEquals(0, $faculty->fresh()->activo, 'La facultad debe estar desactivada');
        $this->assertEquals(0, $career->fresh()->activo, 'La carrera debe estar desactivada');
    }

    /**
     * Test que al desactivar una facultad, se desactivan las carreras en cascada
     * 
     * @test
     */
    public function test_desactivar_facultad_desactiva_carreras_en_cascada()
    {
        // Crear datos necesarios
        $university = University::factory()->create(['activo' => true]);
        $campus = Campus::factory()->create([
            'universidad_id' => $university->universidad_id,
            'activo' => true
        ]);
        $faculty = Faculty::factory()->create([
            'universidad_id' => $university->universidad_id,
            'sede_id' => $campus->sede_id,
            'activo' => true
        ]);
        $career = Career::factory()->create([
            'facultad_id' => $faculty->facultad_id,
            'activo' => true
        ]);

        // Desactivar la facultad
        $response = $this->patchJson(
            "/api/estructura/facultades/{$faculty->facultad_id}/active",
            ['active' => false]
        );

        // Verificar respuesta
        $response->assertStatus(200);

        // Verificar cascada
        $this->assertEquals(1, $university->fresh()->activo, 'La universidad debe permanecer activa');
        $this->assertEquals(1, $campus->fresh()->activo, 'El campus debe permanecer activo');
        $this->assertEquals(0, $faculty->fresh()->activo, 'La facultad debe estar desactivada');
        $this->assertEquals(0, $career->fresh()->activo, 'La carrera debe estar desactivada');
    }

    /**
     * Test que al activar una universidad, se activan campus, facultades y carreras en cascada
     * 
     * @test
     */
    public function test_activar_universidad_activa_jerarquia_organizacional_en_cascada()
    {
        // Crear jerarquía completa desactivada
        $university = University::factory()->create(['activo' => false]);
        $campus = Campus::factory()->create([
            'universidad_id' => $university->universidad_id,
            'activo' => false
        ]);
        $faculty = Faculty::factory()->create([
            'universidad_id' => $university->universidad_id,
            'sede_id' => $campus->sede_id,
            'activo' => false
        ]);
        $career = Career::factory()->create([
            'facultad_id' => $faculty->facultad_id,
            'activo' => false
        ]);

        // Activar la universidad
        $response = $this->patchJson(
            "/api/estructura/universidades/{$university->universidad_id}/active",
            ['active' => true]
        );

        // Verificar respuesta
        $response->assertStatus(200)
            ->assertJson(['message' => 'Estado de la universidad actualizado correctamente. Elementos hijos activados en cascada.']);

        // Verificar que TODOS los elementos se activaron en cascada
        $this->assertEquals(1, $university->fresh()->activo, 'La universidad debe estar activa');
        $this->assertEquals(1, $campus->fresh()->activo, 'El campus debe estar activo');
        $this->assertEquals(1, $faculty->fresh()->activo, 'La facultad debe estar activa');
        $this->assertEquals(1, $career->fresh()->activo, 'La carrera debe estar activa');
    }
}
