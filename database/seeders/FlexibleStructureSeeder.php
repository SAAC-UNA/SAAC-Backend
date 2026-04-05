<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeder del modelo de estructura FLEXIBLE (SINAES 2026).
 *
 * Crea el registro en MODELO_ESTRUCTURA y toda la jerarquía de ELEMENTO:
 *   Dimensión → Pauta → Fuente
 *
 * El modelo flexible NO usa las tablas DIMENSION / COMPONENTE / CRITERIO.
 * Las asignaciones de evidencias se hacen sobre nodos de tipo "fuente".
 *
 * Seguro de correr múltiples veces (insertOrIgnore en modelo, checks en elementos).
 */
class FlexibleStructureSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🌱 Iniciando FlexibleStructureSeeder (SINAES 2026)...');

        // ── 1. Modelo de estructura ───────────────────────────────────────────
        DB::table('MODELO_ESTRUCTURA')->insertOrIgnore([
            'nombre'      => 'SINAES 2026 - Estructura Flexible',
            'tipo'        => 'elemento_flexible',
            'descripcion' => 'Modelo flexible SINAES 2026: Dimensión > Pauta > Fuente. '
                           . 'Los nodos de tipo fuente reciben asignaciones y archivos.',
            'version'     => '2026',
            'activo'      => true,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        $modelo = DB::table('MODELO_ESTRUCTURA')
            ->where('nombre', 'SINAES 2026 - Estructura Flexible')
            ->first();

        $mid = $modelo->modelo_estructura_id;

        // Si ya hay elementos para este modelo, no duplicar
        if (DB::table('ELEMENTO')->where('modelo_estructura_id', $mid)->exists()) {
            $this->command->warn('  ℹ  ELEMENTO ya tiene datos para este modelo — omitiendo inserción.');
            return;
        }

        // Helper closure para insertar un elemento y devolver su ID
        $insert = function (array $data) use ($mid): int {
            return DB::table('ELEMENTO')->insertGetId(array_merge([
                'modelo_estructura_id' => $mid,
                'activo'               => true,
                'created_at'           => now(),
                'updated_at'           => now(),
            ], $data));
        };

        // ═════════════════════════════════════════════════════════════════════
        // DIMENSIÓN 1 — Relación con el contexto
        // ═════════════════════════════════════════════════════════════════════
        $d1 = $insert([
            'padre_id'     => null,
            'tipo'         => 'dimension',
            'nombre'       => 'Relación con el contexto',
            'nomenclatura' => 'D1',
            'descripcion'  => 'Dimensión que evalúa la pertinencia y relación de la carrera con el contexto social, cultural y económico.',
        ]);

        // ── Pauta 1.1 ────────────────────────────────────────────────────────
        $p1_1 = $insert([
            'padre_id'     => $d1,
            'tipo'         => 'pauta',
            'categoria'    => 'A',
            'nombre'       => 'Información y promoción',
            'nomenclatura' => 'P1.1',
            'descripcion'  => 'La carrera dispone de información actualizada, veraz y oportuna sobre sus características, requisitos de ingreso y condiciones de estudio.',
        ]);
        $insert(['padre_id' => $p1_1, 'tipo' => 'fuente',
            'nombre'       => 'Materiales informativos públicos',
            'nomenclatura' => 'F1.1.1',
            'descripcion'  => 'Materiales informativos públicos sobre la carrera (web, folletos, redes sociales)']);
        $insert(['padre_id' => $p1_1, 'tipo' => 'fuente',
            'nombre'       => 'Documentos con costos y normativas',
            'nomenclatura' => 'F1.1.2',
            'descripcion'  => 'Documentos con costos, normativas, trámites y fechas académicas']);
        $insert(['padre_id' => $p1_1, 'tipo' => 'fuente',
            'nombre'       => 'Constancias de difusión vocacional',
            'nomenclatura' => 'F1.1.3',
            'descripcion'  => 'Constancias de difusión en ferias vocacionales e instituciones educativas']);

        // ── Pauta 1.2 ────────────────────────────────────────────────────────
        $p1_2 = $insert([
            'padre_id'     => $d1,
            'tipo'         => 'pauta',
            'categoria'    => 'A',
            'nombre'       => 'Admisión e ingreso',
            'nomenclatura' => 'P1.2',
            'descripcion'  => 'El proceso de admisión es transparente, difundido y coherente con los objetivos de la carrera.',
        ]);
        $insert(['padre_id' => $p1_2, 'tipo' => 'fuente',
            'nombre'       => 'Normativa de admisión',
            'nomenclatura' => 'F1.2.1',
            'descripcion'  => 'Normativa de admisión vigente con requisitos y procedimientos detallados']);
        $insert(['padre_id' => $p1_2, 'tipo' => 'fuente',
            'nombre'       => 'Estadísticas de admisión',
            'nomenclatura' => 'F1.2.2',
            'descripcion'  => 'Estadísticas anuales de postulantes, admitidos y matrícula efectiva']);
        $insert(['padre_id' => $p1_2, 'tipo' => 'fuente',
            'nombre'       => 'Políticas de inclusión',
            'nomenclatura' => 'F1.2.3',
            'descripcion'  => 'Políticas de equiparación de oportunidades e inclusión para estudiantes con discapacidad']);

        // ── Pauta 1.3 ────────────────────────────────────────────────────────
        $p1_3 = $insert([
            'padre_id'     => $d1,
            'tipo'         => 'pauta',
            'categoria'    => 'B',
            'nombre'       => 'Correspondencia con el contexto',
            'nomenclatura' => 'P1.3',
            'descripcion'  => 'La carrera responde a las necesidades del entorno socioeconómico y laboral.',
        ]);
        $insert(['padre_id' => $p1_3, 'tipo' => 'fuente',
            'nombre'       => 'Estudios de pertinencia',
            'nomenclatura' => 'F1.3.1',
            'descripcion'  => 'Estudios de pertinencia y demanda de la carrera en el mercado laboral']);
        $insert(['padre_id' => $p1_3, 'tipo' => 'fuente',
            'nombre'       => 'Convenios con sector empleador',
            'nomenclatura' => 'F1.3.2',
            'descripcion'  => 'Convenios y vínculos formalizados con el sector empleador y entidades externas']);

        // ═════════════════════════════════════════════════════════════════════
        // DIMENSIÓN 2 — Recursos
        // ═════════════════════════════════════════════════════════════════════
        $d2 = $insert([
            'padre_id'     => null,
            'tipo'         => 'dimension',
            'nombre'       => 'Recursos',
            'nomenclatura' => 'D2',
            'descripcion'  => 'Dimensión que evalúa los recursos humanos, académicos y de infraestructura disponibles para la carrera.',
        ]);

        // ── Pauta 2.1 ────────────────────────────────────────────────────────
        $p2_1 = $insert([
            'padre_id'     => $d2,
            'tipo'         => 'pauta',
            'categoria'    => 'A',
            'nombre'       => 'Plan de estudios',
            'nomenclatura' => 'P2.1',
            'descripcion'  => 'El plan de estudios es pertinente, actualizado y coherente con los objetivos institucionales.',
        ]);
        $insert(['padre_id' => $p2_1, 'tipo' => 'fuente',
            'nombre'       => 'Documento oficial del plan',
            'nomenclatura' => 'F2.1.1',
            'descripcion'  => 'Documento oficial del plan de estudios aprobado por las instancias competentes']);
        $insert(['padre_id' => $p2_1, 'tipo' => 'fuente',
            'nombre'       => 'Malla curricular',
            'nomenclatura' => 'F2.1.2',
            'descripcion'  => 'Malla curricular con descripción de cursos, créditos, requisitos y correlaciones']);
        $insert(['padre_id' => $p2_1, 'tipo' => 'fuente',
            'nombre'       => 'Perfil de ingreso y egreso',
            'nomenclatura' => 'F2.1.3',
            'descripcion'  => 'Perfil de ingreso del estudiante y perfil del graduado con competencias esperadas']);

        // ── Pauta 2.2 ────────────────────────────────────────────────────────
        $p2_2 = $insert([
            'padre_id'     => $d2,
            'tipo'         => 'pauta',
            'categoria'    => 'A',
            'nombre'       => 'Personal académico',
            'nomenclatura' => 'P2.2',
            'descripcion'  => 'El personal académico posee la formación, experiencia y desempeño adecuados para el desarrollo de la carrera.',
        ]);
        $insert(['padre_id' => $p2_2, 'tipo' => 'fuente',
            'nombre'       => 'Currículos del personal académico',
            'nomenclatura' => 'F2.2.1',
            'descripcion'  => 'Currículos vitae actualizados del personal académico con grado y experiencia']);
        $insert(['padre_id' => $p2_2, 'tipo' => 'fuente',
            'nombre'       => 'Normativa de contratación docente',
            'nomenclatura' => 'F2.2.2',
            'descripcion'  => 'Normativa de contratación, evaluación y promoción del personal académico']);
        $insert(['padre_id' => $p2_2, 'tipo' => 'fuente',
            'nombre'       => 'Plan de capacitación docente',
            'nomenclatura' => 'F2.2.3',
            'descripcion'  => 'Plan de capacitación y desarrollo profesional continuo del cuerpo docente']);

        // ── Pauta 2.3 ────────────────────────────────────────────────────────
        $p2_3 = $insert([
            'padre_id'     => $d2,
            'tipo'         => 'pauta',
            'categoria'    => 'B',
            'nombre'       => 'Infraestructura y recursos de apoyo',
            'nomenclatura' => 'P2.3',
            'descripcion'  => 'La carrera cuenta con infraestructura física y tecnológica suficiente para el proceso educativo.',
        ]);
        $insert(['padre_id' => $p2_3, 'tipo' => 'fuente',
            'nombre'       => 'Inventario de instalaciones',
            'nomenclatura' => 'F2.3.1',
            'descripcion'  => 'Inventario actualizado de instalaciones, aulas, laboratorios y equipos disponibles']);
        $insert(['padre_id' => $p2_3, 'tipo' => 'fuente',
            'nombre'       => 'Acceso a plataformas y biblioteca',
            'nomenclatura' => 'F2.3.2',
            'descripcion'  => 'Acceso y uso de plataformas virtuales, bases de datos académicas y biblioteca']);

        // ═════════════════════════════════════════════════════════════════════
        // DIMENSIÓN 3 — Proceso educativo
        // ═════════════════════════════════════════════════════════════════════
        $d3 = $insert([
            'padre_id'     => null,
            'tipo'         => 'dimension',
            'nombre'       => 'Proceso educativo',
            'nomenclatura' => 'D3',
            'descripcion'  => 'Dimensión que evalúa el desarrollo del proceso de enseñanza-aprendizaje y la gestión académica de la carrera.',
        ]);

        // ── Pauta 3.1 ────────────────────────────────────────────────────────
        $p3_1 = $insert([
            'padre_id'     => $d3,
            'tipo'         => 'pauta',
            'categoria'    => 'A',
            'nombre'       => 'Metodología de enseñanza-aprendizaje',
            'nomenclatura' => 'P3.1',
            'descripcion'  => 'Las estrategias de enseñanza-aprendizaje son pertinentes y promueven el logro del perfil de egreso.',
        ]);
        $insert(['padre_id' => $p3_1, 'tipo' => 'fuente',
            'nombre'       => 'Programas de cursos',
            'nomenclatura' => 'F3.1.1',
            'descripcion'  => 'Programas de cursos con estrategias didácticas, objetivos y criterios de evaluación']);
        $insert(['padre_id' => $p3_1, 'tipo' => 'fuente',
            'nombre'       => 'Modalidades innovadoras de enseñanza',
            'nomenclatura' => 'F3.1.2',
            'descripcion'  => 'Evidencias de modalidades innovadoras de enseñanza implementadas (virtual, híbrido, proyectos)']);

        // ── Pauta 3.2 ────────────────────────────────────────────────────────
        $p3_2 = $insert([
            'padre_id'     => $d3,
            'tipo'         => 'pauta',
            'categoria'    => 'A',
            'nombre'       => 'Gestión y administración de la carrera',
            'nomenclatura' => 'P3.2',
            'descripcion'  => 'La carrera posee mecanismos efectivos de planificación, gestión y mejora continua.',
        ]);
        $insert(['padre_id' => $p3_2, 'tipo' => 'fuente',
            'nombre'       => 'Plan operativo anual',
            'nomenclatura' => 'F3.2.1',
            'descripcion'  => 'Plan operativo anual y plan de desarrollo a mediano y largo plazo de la carrera']);
        $insert(['padre_id' => $p3_2, 'tipo' => 'fuente',
            'nombre'       => 'Actas de coordinación académica',
            'nomenclatura' => 'F3.2.2',
            'descripcion'  => 'Actas de reuniones de coordinación académica y administrativa del período']);
        $insert(['padre_id' => $p3_2, 'tipo' => 'fuente',
            'nombre'       => 'Informes de autoevaluación',
            'nomenclatura' => 'F3.2.3',
            'descripcion'  => 'Informes de autoevaluación y seguimiento de planes y acciones de mejora']);

        // ═════════════════════════════════════════════════════════════════════
        // DIMENSIÓN 4 — Resultados
        // ═════════════════════════════════════════════════════════════════════
        $d4 = $insert([
            'padre_id'     => null,
            'tipo'         => 'dimension',
            'nombre'       => 'Resultados',
            'nomenclatura' => 'D4',
            'descripcion'  => 'Dimensión que evalúa los logros académicos de los estudiantes, índices de graduación e impacto de los graduados en el entorno.',
        ]);

        // ── Pauta 4.1 ────────────────────────────────────────────────────────
        $p4_1 = $insert([
            'padre_id'     => $d4,
            'tipo'         => 'pauta',
            'categoria'    => 'A',
            'nombre'       => 'Desempeño estudiantil',
            'nomenclatura' => 'P4.1',
            'descripcion'  => 'El desempeño académico de los estudiantes es monitoreado y se toman acciones de apoyo oportunas.',
        ]);
        $insert(['padre_id' => $p4_1, 'tipo' => 'fuente',
            'nombre'       => 'Estadísticas de rendimiento académico',
            'nomenclatura' => 'F4.1.1',
            'descripcion'  => 'Estadísticas de rendimiento académico, reprobación y deserción por período lectivo']);
        $insert(['padre_id' => $p4_1, 'tipo' => 'fuente',
            'nombre'       => 'Reglamento de evaluación',
            'nomenclatura' => 'F4.1.2',
            'descripcion'  => 'Reglamento de evaluación de los aprendizajes vigente y difundido']);

        // ── Pauta 4.2 ────────────────────────────────────────────────────────
        $p4_2 = $insert([
            'padre_id'     => $d4,
            'tipo'         => 'pauta',
            'categoria'    => 'B',
            'nombre'       => 'Graduados e impacto en el entorno',
            'nomenclatura' => 'P4.2',
            'descripcion'  => 'Los graduados se insertan exitosamente en el mercado laboral y la carrera mide su impacto social.',
        ]);
        $insert(['padre_id' => $p4_2, 'tipo' => 'fuente',
            'nombre'       => 'Estudios de seguimiento de graduados',
            'nomenclatura' => 'F4.2.1',
            'descripcion'  => 'Estudios de seguimiento de graduados (empleabilidad, satisfacción, impacto en entorno)']);
        $insert(['padre_id' => $p4_2, 'tipo' => 'fuente',
            'nombre'       => 'Estadísticas de graduación',
            'nomenclatura' => 'F4.2.2',
            'descripcion'  => 'Estadísticas de graduación por cohorte y tiempo promedio de egreso']);

        // ─────────────────────────────────────────────────────────────────────
        $total = DB::table('ELEMENTO')->where('modelo_estructura_id', $mid)->count();
        $this->command->info("✅ FlexibleStructureSeeder completado — {$total} elementos creados.");
        $this->command->info('   Estructura: 4 Dimensiones → 10 Pautas → 25 Fuentes');
    }
}
