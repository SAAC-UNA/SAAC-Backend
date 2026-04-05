<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeder del modelo de estructura TRADICIONAL (SINAES 2018).
 *
 * Consolida en un solo archivo la jerarquía completa:
 *   Dimensión → Componente → Criterio → Estándar + Evidencia
 *
 * Este modelo usa las tablas fijas DIMENSION / COMPONENTE / CRITERIO / ESTANDAR / EVIDENCIA.
 * Los registros de MODELO_ESTRUCTURA (id=1) ya son insertados por la migración 007a.
 *
 * Seguro de correr múltiples veces (insertOrIgnore / checks de existencia).
 */
class TraditionalStructureSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🌱 Iniciando TraditionalStructureSeeder (SINAES 2018)...');

        // ═════════════════════════════════════════════════════════════════════
        // DIMENSIONES
        // ═════════════════════════════════════════════════════════════════════
        if (DB::table('DIMENSION')->count() === 0) {
            DB::table('DIMENSION')->insert([
                ['nomenclatura' => '1', 'nombre' => 'Relación con el contexto', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
                ['nomenclatura' => '2', 'nombre' => 'Recursos',                  'activo' => true, 'created_at' => now(), 'updated_at' => now()],
                ['nomenclatura' => '3', 'nombre' => 'Proceso educativo',          'activo' => true, 'created_at' => now(), 'updated_at' => now()],
                ['nomenclatura' => '4', 'nombre' => 'Resultados',                 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ]);
            $this->command->info('  ✓ 4 Dimensiones');
        } else {
            $this->command->warn('  ℹ  DIMENSION ya tiene datos — omitiendo inserción.');
        }

        $dim = fn(string $n) => DB::table('DIMENSION')->where('nomenclatura', $n)->value('dimension_id');

        // ═════════════════════════════════════════════════════════════════════
        // COMPONENTES
        // ═════════════════════════════════════════════════════════════════════
        if (DB::table('COMPONENTE')->count() === 0) {
            DB::table('COMPONENTE')->insert([
                ['dimension_id' => $dim('1'), 'nomenclatura' => '1.1', 'nombre' => 'Información y promoción',            'activo' => true, 'created_at' => now(), 'updated_at' => now()],
                ['dimension_id' => $dim('1'), 'nomenclatura' => '1.2', 'nombre' => 'Proceso de admisión e ingreso',      'activo' => true, 'created_at' => now(), 'updated_at' => now()],
                ['dimension_id' => $dim('1'), 'nomenclatura' => '1.3', 'nombre' => 'Correspondencia con el contexto',    'activo' => true, 'created_at' => now(), 'updated_at' => now()],
                ['dimension_id' => $dim('2'), 'nomenclatura' => '2.1', 'nombre' => 'Plan de estudios',                   'activo' => true, 'created_at' => now(), 'updated_at' => now()],
                ['dimension_id' => $dim('2'), 'nomenclatura' => '2.2', 'nombre' => 'Personal académico',                 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
                ['dimension_id' => $dim('2'), 'nomenclatura' => '2.4', 'nombre' => 'Infraestructura',                    'activo' => true, 'created_at' => now(), 'updated_at' => now()],
                ['dimension_id' => $dim('3'), 'nomenclatura' => '3.1', 'nombre' => 'Desarrollo docente',                 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
                ['dimension_id' => $dim('3'), 'nomenclatura' => '3.2', 'nombre' => 'Metodología enseñanza-aprendizaje', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
                ['dimension_id' => $dim('3'), 'nomenclatura' => '3.3', 'nombre' => 'Gestión de la carrera',             'activo' => true, 'created_at' => now(), 'updated_at' => now()],
                ['dimension_id' => $dim('4'), 'nomenclatura' => '4.1', 'nombre' => 'Desempeño estudiantil',             'activo' => true, 'created_at' => now(), 'updated_at' => now()],
                ['dimension_id' => $dim('4'), 'nomenclatura' => '4.2', 'nombre' => 'Graduados',                          'activo' => true, 'created_at' => now(), 'updated_at' => now()],
                ['dimension_id' => $dim('4'), 'nomenclatura' => '4.3', 'nombre' => 'Proyección de la carrera',          'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ]);
            $this->command->info('  ✓ 12 Componentes');
        } else {
            $this->command->warn('  ℹ  COMPONENTE ya tiene datos — omitiendo inserción.');
        }

        $comp = fn(string $n) => DB::table('COMPONENTE')->where('nomenclatura', $n)->value('componente_id');

        // ═════════════════════════════════════════════════════════════════════
        // CRITERIOS
        // ═════════════════════════════════════════════════════════════════════
        $criterios = [
            // Componente 1.1 — Información y promoción
            ['componente_id' => $comp('1.1'), 'nomenclatura' => '1.1.1',
                'descripcion' => 'La carrera dispone de información actualizada, veraz y oportuna sobre sus características, requisitos de ingreso y condiciones de estudio.'],
            ['componente_id' => $comp('1.1'), 'nomenclatura' => '1.1.2',
                'descripcion' => 'La información sobre la carrera se divulga por medios accesibles y adecuados para los distintos públicos de interés.'],

            // Componente 1.2 — Proceso de admisión e ingreso
            ['componente_id' => $comp('1.2'), 'nomenclatura' => '1.2.1',
                'descripcion' => 'El proceso de admisión es transparente, difundido y coherente con los objetivos de la carrera.'],
            ['componente_id' => $comp('1.2'), 'nomenclatura' => '1.2.2',
                'descripcion' => 'Se garantiza igualdad de oportunidades de acceso sin discriminación de ningún tipo.'],

            // Componente 2.1 — Plan de estudios
            ['componente_id' => $comp('2.1'), 'nomenclatura' => '2.1.1',
                'descripcion' => 'El plan de estudios cuenta con objetivos, perfil de egreso y estructura curricular debidamente documentados y aprobados.'],
            ['componente_id' => $comp('2.1'), 'nomenclatura' => '2.1.2',
                'descripcion' => 'Los fines y objetivos del plan de estudios son coherentes con los postulados institucionales.'],

            // Componente 2.2 — Personal académico
            ['componente_id' => $comp('2.2'), 'nomenclatura' => '2.2.1',
                'descripcion' => 'El personal académico posee formación y experiencia acorde con las exigencias de los cursos que imparte.'],
            ['componente_id' => $comp('2.2'), 'nomenclatura' => '2.2.3',
                'descripcion' => 'Existen mecanismos de evaluación del desempeño docente y planes de mejora continua.'],

            // Componente 3.1 — Desarrollo docente
            ['componente_id' => $comp('3.1'), 'nomenclatura' => '3.1.1',
                'descripcion' => 'La carrera promueve la capacitación y actualización permanente del personal académico.'],
            ['componente_id' => $comp('3.1'), 'nomenclatura' => '3.1.2',
                'descripcion' => 'Existen incentivos y facilidades institucionales para el desarrollo profesional del docente.'],

            // Componente 4.1 — Desempeño estudiantil
            ['componente_id' => $comp('4.1'), 'nomenclatura' => '4.1.1',
                'descripcion' => 'El rendimiento académico de los estudiantes es monitoreado de forma sistemática.'],
            ['componente_id' => $comp('4.1'), 'nomenclatura' => '4.1.2',
                'descripcion' => 'Se aplican acciones de apoyo para reducir la reprobación y la deserción estudiantil.'],
        ];

        if (DB::table('CRITERIO')->count() === 0) {
            foreach ($criterios as &$c) {
                $c['activo']     = true;
                $c['created_at'] = now();
                $c['updated_at'] = now();
            }
            DB::table('CRITERIO')->insert($criterios);
            $this->command->info('  ✓ 12 Criterios');
        } else {
            $this->command->warn('  ℹ  CRITERIO ya tiene datos — omitiendo inserción.');
        }

        $crit = fn(string $n) => DB::table('CRITERIO')->where('nomenclatura', $n)->value('criterio_id');

        // ═════════════════════════════════════════════════════════════════════
        // ESTÁNDARES (uno por cada criterio — 12 en total)
        // ═════════════════════════════════════════════════════════════════════
        if (DB::table('ESTANDAR')->count() === 0) {
            DB::table('ESTANDAR')->insert([
                // Criterio 1.1.1
                ['criterio_id' => $crit('1.1.1'),
                    'descripcion' => 'La carrera debe contar al menos con un material informativo actualizado y de acceso público.',
                    'activo' => true, 'created_at' => now(), 'updated_at' => now()],
                // Criterio 1.1.2
                ['criterio_id' => $crit('1.1.2'),
                    'descripcion' => 'Al menos el 70% de los estudiantes reporta que recibe la información necesaria para su vida académica de forma oportuna.',
                    'activo' => true, 'created_at' => now(), 'updated_at' => now()],
                // Criterio 1.2.1
                ['criterio_id' => $crit('1.2.1'),
                    'descripcion' => 'Existe normativa aprobada y difundida sobre el proceso de admisión, actualizada en el último ciclo.',
                    'activo' => true, 'created_at' => now(), 'updated_at' => now()],
                // Criterio 1.2.2
                ['criterio_id' => $crit('1.2.2'),
                    'descripcion' => 'El proceso de admisión garantiza igualdad de oportunidades sin discriminación de ningún tipo.',
                    'activo' => true, 'created_at' => now(), 'updated_at' => now()],
                // Criterio 2.1.1
                ['criterio_id' => $crit('2.1.1'),
                    'descripcion' => 'Todos los cursos — el 100% — deben contar con sus respectivos programas completos y aprobados.',
                    'activo' => true, 'created_at' => now(), 'updated_at' => now()],
                // Criterio 2.1.2
                ['criterio_id' => $crit('2.1.2'),
                    'descripcion' => 'Los fines y objetivos del plan de estudios son congruentes con los postulados institucionales y se evidencia en la práctica.',
                    'activo' => true, 'created_at' => now(), 'updated_at' => now()],
                // Criterio 2.2.1
                ['criterio_id' => $crit('2.2.1'),
                    'descripcion' => 'Al menos el 60% del personal académico posee grado mínimo de Licenciatura en el área de especialidad.',
                    'activo' => true, 'created_at' => now(), 'updated_at' => now()],
                // Criterio 2.2.3
                ['criterio_id' => $crit('2.2.3'),
                    'descripcion' => 'La totalidad del personal académico es evaluado al menos una vez por período lectivo mediante mecanismos formales.',
                    'activo' => true, 'created_at' => now(), 'updated_at' => now()],
                // Criterio 3.1.1
                ['criterio_id' => $crit('3.1.1'),
                    'descripcion' => 'Al menos el 80% del personal académico participa en actividades de capacitación o actualización por año.',
                    'activo' => true, 'created_at' => now(), 'updated_at' => now()],
                // Criterio 3.1.2
                ['criterio_id' => $crit('3.1.2'),
                    'descripcion' => 'La institución dispone de al menos dos mecanismos de incentivo o apoyo al desarrollo profesional docente activos.',
                    'activo' => true, 'created_at' => now(), 'updated_at' => now()],
                // Criterio 4.1.1
                ['criterio_id' => $crit('4.1.1'),
                    'descripcion' => 'La tasa de aprobación general de los cursos del plan de estudios es igual o superior al 70%.',
                    'activo' => true, 'created_at' => now(), 'updated_at' => now()],
                // Criterio 4.1.2
                ['criterio_id' => $crit('4.1.2'),
                    'descripcion' => 'La carrera cuenta con al menos un programa de apoyo estudiantil activo con cobertura y resultados documentados.',
                    'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ]);
            $this->command->info('  ✓ 12 Estándares');
        } else {
            $this->command->warn('  ℹ  ESTANDAR ya tiene datos — omitiendo inserción.');
        }

        // ═════════════════════════════════════════════════════════════════════
        // EVIDENCIAS (muestra representativa — nomenclatura SINAES #20-28, #40-43)
        // ═════════════════════════════════════════════════════════════════════
        if (DB::table('EVIDENCIA')->count() === 0) {
            DB::table('EVIDENCIA')->insert([
                // Criterio 1.1.1
                ['criterio_id' => $crit('1.1.1'), 'estado' => 'Pendiente', 'nomenclatura' => '20',
                    'descripcion' => 'Lista descriptiva de los materiales informativos disponibles sobre la carrera',
                    'activo' => true, 'created_at' => now(), 'updated_at' => now()],
                ['criterio_id' => $crit('1.1.1'), 'estado' => 'Pendiente', 'nomenclatura' => '21',
                    'descripcion' => 'Descripción de la estrategia de comunicación y divulgación de la carrera',
                    'activo' => true, 'created_at' => now(), 'updated_at' => now()],

                // Criterio 1.1.2
                ['criterio_id' => $crit('1.1.2'), 'estado' => 'Pendiente', 'nomenclatura' => '22',
                    'descripcion' => 'Porcentaje de estudiantes que reciben información requerida para su vida académica',
                    'activo' => true, 'created_at' => now(), 'updated_at' => now()],
                ['criterio_id' => $crit('1.1.2'), 'estado' => 'Pendiente', 'nomenclatura' => '23',
                    'descripcion' => 'Porcentaje de estudiantes que opina sobre entrega oportuna de información',
                    'activo' => true, 'created_at' => now(), 'updated_at' => now()],

                // Criterio 1.2.1
                ['criterio_id' => $crit('1.2.1'), 'estado' => 'Pendiente', 'nomenclatura' => '24',
                    'descripcion' => 'Normativa y lista de trámites y requisitos de ingreso a la carrera',
                    'activo' => true, 'created_at' => now(), 'updated_at' => now()],
                ['criterio_id' => $crit('1.2.1'), 'estado' => 'Pendiente', 'nomenclatura' => '25',
                    'descripcion' => 'Medios de difusión de trámites y requisitos de ingreso',
                    'activo' => true, 'created_at' => now(), 'updated_at' => now()],

                // Criterio 1.2.2
                ['criterio_id' => $crit('1.2.2'), 'estado' => 'Pendiente', 'nomenclatura' => '26',
                    'descripcion' => 'Descripción de políticas que garantizan igualdad de oportunidades de acceso',
                    'activo' => true, 'created_at' => now(), 'updated_at' => now()],
                ['criterio_id' => $crit('1.2.2'), 'estado' => 'Pendiente', 'nomenclatura' => '27',
                    'descripcion' => 'Distribución de estudiantes admitidos por sexo y nacionalidad',
                    'activo' => true, 'created_at' => now(), 'updated_at' => now()],
                ['criterio_id' => $crit('1.2.2'), 'estado' => 'Pendiente', 'nomenclatura' => '28',
                    'descripcion' => 'Descripción de condiciones y apoyos para personas con discapacidad',
                    'activo' => true, 'created_at' => now(), 'updated_at' => now()],

                // Criterio 2.1.1
                ['criterio_id' => $crit('2.1.1'), 'estado' => 'Pendiente', 'nomenclatura' => '40',
                    'descripcion' => 'Documento oficial con antecedentes y fundamentos conceptuales del plan de estudios',
                    'activo' => true, 'created_at' => now(), 'updated_at' => now()],
                ['criterio_id' => $crit('2.1.1'), 'estado' => 'Pendiente', 'nomenclatura' => '41',
                    'descripcion' => 'Descripción de medios de divulgación del documento del plan de estudios',
                    'activo' => true, 'created_at' => now(), 'updated_at' => now()],

                // Criterio 2.1.2
                ['criterio_id' => $crit('2.1.2'), 'estado' => 'Pendiente', 'nomenclatura' => '42',
                    'descripcion' => 'Justificación de congruencia de fines con postulados institucionales',
                    'activo' => true, 'created_at' => now(), 'updated_at' => now()],
                ['criterio_id' => $crit('2.1.2'), 'estado' => 'Pendiente', 'nomenclatura' => '43',
                    'descripcion' => 'Porcentaje del personal que considera que los fines guían el proceso educativo',
                    'activo' => true, 'created_at' => now(), 'updated_at' => now()],

                // Criterio 2.2.1
                ['criterio_id' => $crit('2.2.1'), 'estado' => 'Pendiente', 'nomenclatura' => '60',
                    'descripcion' => 'Currículos vitae del personal académico con grado académico y experiencia docente',
                    'activo' => true, 'created_at' => now(), 'updated_at' => now()],
                ['criterio_id' => $crit('2.2.1'), 'estado' => 'Pendiente', 'nomenclatura' => '61',
                    'descripcion' => 'Listado del personal académico con tipo de nombramiento y carga horaria',
                    'activo' => true, 'created_at' => now(), 'updated_at' => now()],

                // Criterio 2.2.3
                ['criterio_id' => $crit('2.2.3'), 'estado' => 'Pendiente', 'nomenclatura' => '75',
                    'descripcion' => 'Reglamento de evaluación del desempeño del personal académico',
                    'activo' => true, 'created_at' => now(), 'updated_at' => now()],
                ['criterio_id' => $crit('2.2.3'), 'estado' => 'Pendiente', 'nomenclatura' => '76',
                    'descripcion' => 'Resultados cuantitativos de la evaluación docente aplicada en el período',
                    'activo' => true, 'created_at' => now(), 'updated_at' => now()],
                ['criterio_id' => $crit('2.2.3'), 'estado' => 'Pendiente', 'nomenclatura' => '77',
                    'descripcion' => 'Plan de mejora del personal académico derivado de los resultados de evaluación',
                    'activo' => true, 'created_at' => now(), 'updated_at' => now()],

                // Criterio 3.1.1
                ['criterio_id' => $crit('3.1.1'), 'estado' => 'Pendiente', 'nomenclatura' => '100',
                    'descripcion' => 'Plan de capacitación y actualización del personal académico',
                    'activo' => true, 'created_at' => now(), 'updated_at' => now()],
                ['criterio_id' => $crit('3.1.1'), 'estado' => 'Pendiente', 'nomenclatura' => '101',
                    'descripcion' => 'Registro de actividades de formación completadas por el personal en el último año',
                    'activo' => true, 'created_at' => now(), 'updated_at' => now()],

                // Criterio 3.1.2
                ['criterio_id' => $crit('3.1.2'), 'estado' => 'Pendiente', 'nomenclatura' => '110',
                    'descripcion' => 'Normativa institucional de apoyo al desarrollo profesional del personal académico',
                    'activo' => true, 'created_at' => now(), 'updated_at' => now()],
                ['criterio_id' => $crit('3.1.2'), 'estado' => 'Pendiente', 'nomenclatura' => '111',
                    'descripcion' => 'Registro de estímulos, becas y reconocimientos otorgados al personal académico',
                    'activo' => true, 'created_at' => now(), 'updated_at' => now()],

                // Criterio 4.1.1
                ['criterio_id' => $crit('4.1.1'), 'estado' => 'Pendiente', 'nomenclatura' => '200',
                    'descripcion' => 'Estadísticas de rendimiento académico por curso y período lectivo',
                    'activo' => true, 'created_at' => now(), 'updated_at' => now()],
                ['criterio_id' => $crit('4.1.1'), 'estado' => 'Pendiente', 'nomenclatura' => '201',
                    'descripcion' => 'Tasa de aprobación, reprobación y deserción por cohorte',
                    'activo' => true, 'created_at' => now(), 'updated_at' => now()],

                // Criterio 4.1.2
                ['criterio_id' => $crit('4.1.2'), 'estado' => 'Pendiente', 'nomenclatura' => '202',
                    'descripcion' => 'Programas de tutorías, becas y apoyo académico disponibles',
                    'activo' => true, 'created_at' => now(), 'updated_at' => now()],
                ['criterio_id' => $crit('4.1.2'), 'estado' => 'Pendiente', 'nomenclatura' => '203',
                    'descripcion' => 'Estadísticas de uso de servicios de apoyo estudiantil',
                    'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ]);
            $this->command->info('  ✓ 26 Evidencias (muestra representativa SINAES: #20-28, #40-43, #60-61, #75-77, #100-101, #110-111, #200-203)');
        } else {
            $this->command->warn('  ℹ  EVIDENCIA ya tiene datos — omitiendo inserción.');
        }

        $this->command->info('✅ TraditionalStructureSeeder completado.');
        $this->command->info('   Estructura: 4 Dimensiones → 12 Componentes → 12 Criterios → 12 Estándares → 26 Evidencias');
    }
}
