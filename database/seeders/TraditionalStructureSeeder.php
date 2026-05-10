<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeder del modelo de estructura TRADICIONAL (SINAES 2009).
 *
 * Consolida en un solo archivo la jerarquía completa del Manual de Acreditación
 * Oficial de Carreras de Grado del SINAES (2009):
 *
 *   Dimensión → Componente → Criterio → Estándar(es) + Evidencia(s)
 *
 * Incluye además los bloques transversales de Admisibilidad (A) y Sostenibilidad (S).
 *
 * Totales según el Cuadro No. 2 del manual:
 *   - 5  Dimensiones  (incluyendo D0 Transversal)
 *   - 21 Componentes  (A, S, 1.1–1.3, 2.1–2.7, 3.1–3.6, 4.1–4.3)
 *   - 171 Criterios   (A1-A12, 1.1.1-4.3.1, S1-S10)
 *   - 34  Estándares
 *   - 348 Evidencias
 *
 * Textos corregidos usando el PDF oficial como fuente autoritaria.
 * Seguro de correr múltiples veces (checks de count() === 0).
 */
class TraditionalStructureSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🌱 Iniciando TraditionalStructureSeeder (SINAES 2009)...');

        // ═══════════════════════════════════════════════════════════════════════
        // DIMENSIONES
        // ═══════════════════════════════════════════════════════════════════════
        if (DB::table('DIMENSION')->count() === 0) {
            DB::table('DIMENSION')->insert([
                ['nomenclatura' => 'D0', 'nombre' => 'Transversal',             'activo' => true, 'created_at' => now(), 'updated_at' => now()],
                ['nomenclatura' => '1',  'nombre' => 'Relación con el contexto', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
                ['nomenclatura' => '2',  'nombre' => 'Recursos',                 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
                ['nomenclatura' => '3',  'nombre' => 'Proceso educativo',        'activo' => true, 'created_at' => now(), 'updated_at' => now()],
                ['nomenclatura' => '4',  'nombre' => 'Resultados',               'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ]);
            $this->command->info('  ✓ 5 Dimensiones');
        } else {
            $this->command->warn('  ℹ  DIMENSION ya tiene datos — omitiendo inserción.');
        }

        $dim = fn (string $n) => DB::table('DIMENSION')->where('nomenclatura', $n)->value('dimension_id');

        // ═══════════════════════════════════════════════════════════════════════
        // COMPONENTES  (21 en total)
        // ═══════════════════════════════════════════════════════════════════════
        if (DB::table('COMPONENTE')->count() === 0) {
            DB::table('COMPONENTE')->insert([
                // Transversales
                ['dimension_id' => $dim('D0'), 'nomenclatura' => 'A',   'nombre' => 'Admisibilidad',                          'activo' => true, 'created_at' => now(), 'updated_at' => now()],
                ['dimension_id' => $dim('D0'), 'nomenclatura' => 'S',   'nombre' => 'Sostenibilidad',                         'activo' => true, 'created_at' => now(), 'updated_at' => now()],
                // D1
                ['dimension_id' => $dim('1'),  'nomenclatura' => '1.1', 'nombre' => 'Información y promoción',                'activo' => true, 'created_at' => now(), 'updated_at' => now()],
                ['dimension_id' => $dim('1'),  'nomenclatura' => '1.2', 'nombre' => 'Proceso de admisión e ingreso',          'activo' => true, 'created_at' => now(), 'updated_at' => now()],
                ['dimension_id' => $dim('1'),  'nomenclatura' => '1.3', 'nombre' => 'Correspondencia con el contexto',        'activo' => true, 'created_at' => now(), 'updated_at' => now()],
                // D2
                ['dimension_id' => $dim('2'),  'nomenclatura' => '2.1', 'nombre' => 'Plan de estudios',                      'activo' => true, 'created_at' => now(), 'updated_at' => now()],
                ['dimension_id' => $dim('2'),  'nomenclatura' => '2.2', 'nombre' => 'Personal académico',                    'activo' => true, 'created_at' => now(), 'updated_at' => now()],
                ['dimension_id' => $dim('2'),  'nomenclatura' => '2.3', 'nombre' => 'Personal administrativo',               'activo' => true, 'created_at' => now(), 'updated_at' => now()],
                ['dimension_id' => $dim('2'),  'nomenclatura' => '2.4', 'nombre' => 'Infraestructura',                       'activo' => true, 'created_at' => now(), 'updated_at' => now()],
                ['dimension_id' => $dim('2'),  'nomenclatura' => '2.5', 'nombre' => 'Centro de información y recursos',      'activo' => true, 'created_at' => now(), 'updated_at' => now()],
                ['dimension_id' => $dim('2'),  'nomenclatura' => '2.6', 'nombre' => 'Equipo y materiales',                   'activo' => true, 'created_at' => now(), 'updated_at' => now()],
                ['dimension_id' => $dim('2'),  'nomenclatura' => '2.7', 'nombre' => 'Finanzas y presupuestos',               'activo' => true, 'created_at' => now(), 'updated_at' => now()],
                // D3
                ['dimension_id' => $dim('3'),  'nomenclatura' => '3.1', 'nombre' => 'Desarrollo docente',                   'activo' => true, 'created_at' => now(), 'updated_at' => now()],
                ['dimension_id' => $dim('3'),  'nomenclatura' => '3.2', 'nombre' => 'Metodología de enseñanza y aprendizaje', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
                ['dimension_id' => $dim('3'),  'nomenclatura' => '3.3', 'nombre' => 'Gestión de la carrera',                'activo' => true, 'created_at' => now(), 'updated_at' => now()],
                ['dimension_id' => $dim('3'),  'nomenclatura' => '3.4', 'nombre' => 'Investigación',                        'activo' => true, 'created_at' => now(), 'updated_at' => now()],
                ['dimension_id' => $dim('3'),  'nomenclatura' => '3.5', 'nombre' => 'Extensión',                            'activo' => true, 'created_at' => now(), 'updated_at' => now()],
                ['dimension_id' => $dim('3'),  'nomenclatura' => '3.6', 'nombre' => 'Vida estudiantil',                     'activo' => true, 'created_at' => now(), 'updated_at' => now()],
                // D4
                ['dimension_id' => $dim('4'),  'nomenclatura' => '4.1', 'nombre' => 'Desempeño estudiantil',                'activo' => true, 'created_at' => now(), 'updated_at' => now()],
                ['dimension_id' => $dim('4'),  'nomenclatura' => '4.2', 'nombre' => 'Graduados',                            'activo' => true, 'created_at' => now(), 'updated_at' => now()],
                ['dimension_id' => $dim('4'),  'nomenclatura' => '4.3', 'nombre' => 'Proyección de la carrera',             'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ]);
            $this->command->info('  ✓ 21 Componentes');
        } else {
            $this->command->warn('  ℹ  COMPONENTE ya tiene datos — omitiendo inserción.');
        }

        $comp = fn (string $n) => DB::table('COMPONENTE')->where('nomenclatura', $n)->value('componente_id');

        // ═══════════════════════════════════════════════════════════════════════
        // CRITERIOS  (171 en total — textos corregidos contra el PDF)
        // ═══════════════════════════════════════════════════════════════════════
        if (DB::table('CRITERIO')->count() === 0) {
            $criterios = [
                // ── ADMISIBILIDAD ────────────────────────────────────────────
                ['componente_id' => $comp('A'), 'nomenclatura' => 'A.1',
                    'descripcion' => 'El programa o carrera debe contar con al menos una cohorte de graduados y cinco años de funcionamiento.'],
                ['componente_id' => $comp('A'), 'nomenclatura' => 'A.2',
                    'descripcion' => 'La definición del crédito y el número de créditos asignados a cada curso deben corresponder a la normativa establecida por CONARE y CONESUP.'],
                ['componente_id' => $comp('A'), 'nomenclatura' => 'A.3',
                    'descripcion' => 'La carrera debe contar con ciclos lectivos que cumplan con la duración mínima establecida por la normativa de CONARE y CONESUP.'],
                ['componente_id' => $comp('A'), 'nomenclatura' => 'A.4',
                    'descripcion' => 'El título que se otorga debe coincidir en todos sus extremos con la nomenclatura de grados y títulos de la educación superior aprobada por CONARE o CONESUP.'],
                ['componente_id' => $comp('A'), 'nomenclatura' => 'A.5',
                    'descripcion' => 'El 100% de los estudiantes que se admitan por traslado debe provenir de instituciones y carreras debidamente autorizadas por la entidad jurídicamente competente.'],
                ['componente_id' => $comp('A'), 'nomenclatura' => 'A.6',
                    'descripcion' => 'Los cursos que se equiparen deben tener al menos un 90% de congruencia con los objetivos y contenidos del curso objeto del reconocimiento.'],
                ['componente_id' => $comp('A'), 'nomenclatura' => 'A.7',
                    'descripcion' => 'La carrera podrá equiparar a sus estudiantes hasta un máximo del 40% del total de créditos, por transferencia de una carrera no acreditada oficialmente.'],
                ['componente_id' => $comp('A'), 'nomenclatura' => 'A.8',
                    'descripcion' => 'El 100% de los estudiantes admitidos en la carrera debe poseer el título de Bachiller de Enseñanza Media o uno que haya sido equiparado con este por el Consejo Superior de Educación.'],
                ['componente_id' => $comp('A'), 'nomenclatura' => 'A.9',
                    'descripcion' => 'La equiparación de grados procederá solamente entre instituciones debidamente autorizadas por la entidad competente.'],
                ['componente_id' => $comp('A'), 'nomenclatura' => 'A.10',
                    'descripcion' => 'Todo reconocimiento de un tramo cursado en una institución parauniversitaria debe estar amparado por un convenio específico en el marco del convenio nacional de articulación de la educación superior.'],
                ['componente_id' => $comp('A'), 'nomenclatura' => 'A.11',
                    'descripcion' => 'El 100% de los cursos de la misma carrera debe corresponder a un grado académico igual o superior al que ofrece la carrera.'],
                ['componente_id' => $comp('A'), 'nomenclatura' => 'A.12',
                    'descripcion' => 'Cada carrera podrá reconocer a sus estudiantes, mediante el sistema de reconocimiento de aprendizajes por experiencia, hasta un máximo del 20% de los créditos de la carrera.'],

                // ── DIMENSIÓN 1 · Relación con el contexto ───────────────────
                // 1.1
                ['componente_id' => $comp('1.1'), 'nomenclatura' => '1.1.1',
                    'descripcion' => 'Debe contarse con medios que permitan acceso público a información sobre la carrera, los trámites de ingreso, la duración de los estudios, los requisitos y procedimientos para las convalidaciones y reconocimientos y las tarifas de los trámites académico-administrativos.'],
                ['componente_id' => $comp('1.1'), 'nomenclatura' => '1.1.2',
                    'descripcion' => 'El estudiante debe ser informado oportunamente y de forma veraz, al menos sobre el plan de estudios, tiempo promedio de graduación, costos, normativa, fechas, trámites y servicios.'],
                // 1.2
                ['componente_id' => $comp('1.2'), 'nomenclatura' => '1.2.1',
                    'descripcion' => 'Los trámites y requisitos de ingreso en la carrera deben estar claramente estipulados en una normativa y ser ampliamente difundidos.'],
                ['componente_id' => $comp('1.2'), 'nomenclatura' => '1.2.2',
                    'descripcion' => 'Debe promoverse el acceso a la carrera o programa en igualdad de oportunidades, sin discriminación y con respeto por la diversidad.'],
                // 1.3
                ['componente_id' => $comp('1.3'), 'nomenclatura' => '1.3.1',
                    'descripcion' => 'El plan de estudios debe responder al estado actual de avance o desarrollo de la disciplina –estado del arte– y a la realidad del contexto nacional e internacional, así como al mercado laboral.'],
                ['componente_id' => $comp('1.3'), 'nomenclatura' => '1.3.2',
                    'descripcion' => 'Se debe contar con políticas y acciones concretas que favorezcan la participación de los estudiantes de la carrera en la atención de necesidades del contexto.'],
                ['componente_id' => $comp('1.3'), 'nomenclatura' => '1.3.3',
                    'descripcion' => 'La carrera debe incorporar, durante el proceso formativo, el análisis y estudio de problemas del contexto, y proponer solución a estos desde su especialidad.'],
                ['componente_id' => $comp('1.3'), 'nomenclatura' => '1.3.4',
                    'descripcion' => 'Deben existir estrategias y acciones tendientes a vincular la carrera con la correspondiente comunidad académica, para su retroalimentación y mejora.'],
                ['componente_id' => $comp('1.3'), 'nomenclatura' => '1.3.5',
                    'descripcion' => 'Se debe demostrar que se aprovecha el entorno para experiencias prácticas del estudiantado, según los requerimientos de la carrera.'],
                ['componente_id' => $comp('1.3'), 'nomenclatura' => '1.3.6',
                    'descripcion' => 'La carrera debe demostrar que incorpora elementos que contribuyen a preparar, a los futuros graduados, para enfrentar los cambios del contexto y de la disciplina.'],

                // ── DIMENSIÓN 2 · Recursos ────────────────────────────────────
                // 2.1
                ['componente_id' => $comp('2.1'), 'nomenclatura' => '2.1.1',
                    'descripcion' => 'La carrera debe contar con un documento descriptivo que contenga lo siguiente: antecedentes, fundamentos conceptuales, objetivos, fines, ejes curriculares y orientación metodológica.'],
                ['componente_id' => $comp('2.1'), 'nomenclatura' => '2.1.2',
                    'descripcion' => 'Los fines y objetivos de la carrera deben ser claros y congruentes con los postulados de la institución y guiar adecuadamente el proceso educativo.'],
                ['componente_id' => $comp('2.1'), 'nomenclatura' => '2.1.3',
                    'descripcion' => 'La carrera debe contar con una descripción explícita de los referentes universales y las corrientes del pensamiento que fundamentan el plan de estudios.'],
                ['componente_id' => $comp('2.1'), 'nomenclatura' => '2.1.4',
                    'descripcion' => 'La carrera debe tener un perfil de entrada claramente establecido, congruente con los conocimientos, las habilidades y las actitudes que corresponden a su naturaleza.'],
                ['componente_id' => $comp('2.1'), 'nomenclatura' => '2.1.5',
                    'descripcion' => 'La carrera debe contar con un perfil profesional de salida claramente establecido, congruente con el ejercicio de la profesión y con los contenidos curriculares que constituyen su fundamento.'],
                ['componente_id' => $comp('2.1'), 'nomenclatura' => '2.1.6',
                    'descripcion' => 'Se debe contar con una malla curricular que establezca, según criterios estrictamente académicos, la secuencia de los cursos, según ciclos, y los requisitos y correquisitos de cada uno.'],
                ['componente_id' => $comp('2.1'), 'nomenclatura' => '2.1.7',
                    'descripcion' => 'El plan de estudios debe incluir cursos teóricos, prácticos y teórico-prácticos, de acuerdo con la naturaleza de la carrera.'],
                ['componente_id' => $comp('2.1'), 'nomenclatura' => '2.1.8',
                    'descripcion' => 'El plan de estudios debe establecer mecanismos para la integración de la teoría y la práctica, de acuerdo con la naturaleza de la carrera.'],
                ['componente_id' => $comp('2.1'), 'nomenclatura' => '2.1.9',
                    'descripcion' => 'El plan de estudios debe incorporar contenidos de otras disciplinas afines a la carrera o complementarias, que posibiliten una perspectiva multidisciplinaria.'],
                ['componente_id' => $comp('2.1'), 'nomenclatura' => '2.1.10',
                    'descripcion' => 'Se debe demostrar que el plan de estudios incluye contenidos de ética para el ejercicio profesional.'],
                ['componente_id' => $comp('2.1'), 'nomenclatura' => '2.1.11',
                    'descripcion' => 'El plan de estudios incluye contenidos que estimulan la lectura y el estudio en otro idioma.'],
                ['componente_id' => $comp('2.1'), 'nomenclatura' => '2.1.12',
                    'descripcion' => 'La carrera, de acuerdo con su naturaleza, debe incorporar el uso de tecnologías de información para apoyar el proceso formativo.'],
                ['componente_id' => $comp('2.1'), 'nomenclatura' => '2.1.13',
                    'descripcion' => 'El plan de estudios y las estrategias didácticas deben estimular, en los estudiantes, su capacidad de aprender, y deben incluir componentes orientados a desarrollar, en ellos, pensamientos, principios y prácticas científicas rigurosas relevantes para su disciplina.'],
                ['componente_id' => $comp('2.1'), 'nomenclatura' => '2.1.14',
                    'descripcion' => 'El plan de estudios debe considerar la flexibilidad curricular, la cual, sin distorsionar la secuencia, ha de satisfacer intereses específicos de los estudiantes y la posibilidad de enfatizar en diferentes áreas del conocimiento.'],
                ['componente_id' => $comp('2.1'), 'nomenclatura' => '2.1.15',
                    'descripcion' => 'Se deben ofrecer al estudiantado actividades extracurriculares que complementan el plan de estudios.'],
                ['componente_id' => $comp('2.1'), 'nomenclatura' => '2.1.16',
                    'descripcion' => 'Los estudiantes, para graduarse, tienen que realizar un trabajo final de una de las modalidades que corresponden a la carrera, o su equivalente, cuando el grado académico por obtener lo implique como requisito. (*No se aplica en planes de estudio que ofrecen títulos inferiores a la Licenciatura.)'],
                ['componente_id' => $comp('2.1'), 'nomenclatura' => '2.1.17',
                    'descripcion' => 'El programa de cada curso debe contener al menos los siguientes elementos: nombre del curso, código, ciclo lectivo en el que se ofrece, descripción general, objetivos generales y específicos, créditos, total de horas lectivas semanales divididas en teoría, práctica y laboratorio (cuando corresponda), contenidos temáticos, metodología, estrategias de evaluación de los aprendizajes y bibliografía.'],
                ['componente_id' => $comp('2.1'), 'nomenclatura' => '2.1.18',
                    'descripcion' => 'Los objetivos de los cursos deberán redactarse en términos de los aprendizajes o las competencias que se pretende lograr en los estudiantes, en las esferas cognitiva, de destrezas y actitudinal.'],
                ['componente_id' => $comp('2.1'), 'nomenclatura' => '2.1.19',
                    'descripcion' => 'Los criterios de evaluación de los aprendizajes deben estar explícitos en cada programa de curso.'],
                // 2.2
                ['componente_id' => $comp('2.2'), 'nomenclatura' => '2.2.1',
                    'descripcion' => 'Se debe contar con normativas para el personal académico, que regule sus deberes y derechos en el ejercicio de la actividad docente.'],
                ['componente_id' => $comp('2.2'), 'nomenclatura' => '2.2.2',
                    'descripcion' => 'En los tiempos asignados al personal académico deben incluirse, en la carga docente, las horas de lecciones, de atención de estudiantes fuera de clase, de preparación de lecciones, de elaboración y aplicación de instrumentos de evaluación (exámenes y otros), de revisión y valoración de pruebas, tareas y otros requisitos de los cursos, así como de dirección de trabajos finales de graduación.'],
                ['componente_id' => $comp('2.2'), 'nomenclatura' => '2.2.3',
                    'descripcion' => 'La carrera debe garantizar que su personal académico pueda participar en actividades de docencia, investigación y extensión social.'],
                ['componente_id' => $comp('2.2'), 'nomenclatura' => '2.2.4',
                    'descripcion' => 'La carrera debe contar con personal académico competente; la competencia se definirá con base en el grado académico, la experiencia docente y profesional y la producción académica o profesional.'],
                ['componente_id' => $comp('2.2'), 'nomenclatura' => '2.2.5',
                    'descripcion' => 'Se debe propiciar la diversidad del personal académico en cuanto a género, edad y universidades en que se ha formado.'],
                ['componente_id' => $comp('2.2'), 'nomenclatura' => '2.2.6',
                    'descripcion' => 'Se debe contar con requisitos y procedimientos que garanticen la selección de personal académico idóneo.'],
                ['componente_id' => $comp('2.2'), 'nomenclatura' => '2.2.7',
                    'descripcion' => 'Se deben tener mecanismos para retener a los mejores académicos y contar con planes de sustitución de mediano y largo plazo.'],
                ['componente_id' => $comp('2.2'), 'nomenclatura' => '2.2.8',
                    'descripcion' => 'La carrera debe mantener en ejecución un plan de desarrollo para el personal académico, que estimule la formación en áreas de interés, la obtención de grados académicos superiores y el mejoramiento en aspectos de didáctica universitaria o de la especialidad.'],
                ['componente_id' => $comp('2.2'), 'nomenclatura' => '2.2.9',
                    'descripcion' => 'Se debe contar con incentivos o mecanismos de promoción que se apliquen al personal académico, que propicien su desarrollo profesional.'],
                ['componente_id' => $comp('2.2'), 'nomenclatura' => '2.2.10',
                    'descripcion' => 'El personal académico de tiempo completo debe asegurar su interacción con el estudiantado y favorecer la participación en la vida académica.'],
                ['componente_id' => $comp('2.2'), 'nomenclatura' => '2.2.11',
                    'descripcion' => 'Debe existir personal académico de tiempo completo en una proporción tal que asegure tanto un adecuado nivel de interacción entre éste y el estudiantado, como su amplia participación en las diversas actividades curriculares.'],
                // 2.3
                ['componente_id' => $comp('2.3'), 'nomenclatura' => '2.3.1',
                    'descripcion' => 'La carrera debe contar con personal administrativo, técnico y de apoyo eficientes y suficientes para atender los distintos aspectos de soporte del proceso académico.'],
                ['componente_id' => $comp('2.3'), 'nomenclatura' => '2.3.2',
                    'descripcion' => 'Los procedimientos para la selección de personal, así como la definición de cargos y funciones, deben estar formalmente establecidos, de forma que se garantice la idoneidad de las personas para ocupar los diversos cargos.'],
                ['componente_id' => $comp('2.3'), 'nomenclatura' => '2.3.3',
                    'descripcion' => 'Se debe evaluar y dar seguimiento al personal administrativo, al técnico y al de apoyo, tanto en el cumplimiento de sus funciones como en la calidad y calidez del servicio que brindan.'],
                ['componente_id' => $comp('2.3'), 'nomenclatura' => '2.3.4',
                    'descripcion' => 'Se debe contar con un plan de desarrollo profesional para el personal administrativo, el técnico y el de apoyo, de acuerdo con las necesidades de la carrera.'],
                // 2.4
                ['componente_id' => $comp('2.4'), 'nomenclatura' => '2.4.1',
                    'descripcion' => 'Se debe contar con mecanismos que atiendan la gestión para suplir las necesidades de infraestructura, de acuerdo con las particularidades y necesidades de la carrera.'],
                ['componente_id' => $comp('2.4'), 'nomenclatura' => '2.4.2',
                    'descripcion' => 'La infraestructura que utiliza la carrera debe cumplir con las disposiciones de la normativa para la construcción o habilitación de edificios educativos; en particular, debe cumplir con todo lo establecido en el Reglamento de Construcciones de la Ley N° 4240 del 15 de noviembre de 1968 y lo ordenado por la Ley de Igualdad de Oportunidades para las Personas con Discapacidad.'],
                ['componente_id' => $comp('2.4'), 'nomenclatura' => '2.4.3',
                    'descripcion' => 'La carrera debe disponer de un manual, conocido por el personal académico, administrativo y de apoyo y por los estudiantes, con las normas de seguridad, higiene y salud ocupacional pertinentes, según la naturaleza de la carrera.'],
                ['componente_id' => $comp('2.4'), 'nomenclatura' => '2.4.4',
                    'descripcion' => 'Se debe contar con las condiciones de seguridad, higiene y salud ocupacional requeridas en los diferentes ámbitos de desarrollo de la actividad académica.'],
                ['componente_id' => $comp('2.4'), 'nomenclatura' => '2.4.5',
                    'descripcion' => 'Se debe contar con aulas, auditorios, laboratorios, talleres, biblioteca y otros espacios necesarios, en buen estado, suficientes para el número de personas que los necesitan y amueblados adecuadamente, todo ello según la función que cumplen y la naturaleza de la carrera.'],
                ['componente_id' => $comp('2.4'), 'nomenclatura' => '2.4.6',
                    'descripcion' => 'El personal académico debe tener acceso oportuno a un recinto adecuado para la atención de estudiantes y para la realización de otras actividades propias de su función docente.'],
                ['componente_id' => $comp('2.4'), 'nomenclatura' => '2.4.7',
                    'descripcion' => 'Debe haber oficinas apropiadas y accesibles para las personas vinculadas con la gestión de la carrera y con los servicios administrativos y técnicos básicos.'],
                ['componente_id' => $comp('2.4'), 'nomenclatura' => '2.4.8',
                    'descripcion' => 'Los estudiantes deben contar con espacios para las actividades extra-clase.'],
                // 2.5
                ['componente_id' => $comp('2.5'), 'nomenclatura' => '2.5.1',
                    'descripcion' => 'El personal académico y el estudiantado de la carrera deben tener acceso, al menos, a un centro de información y recursos que cuente con todos los medios y equipos requeridos por ellos.'],
                ['componente_id' => $comp('2.5'), 'nomenclatura' => '2.5.2',
                    'descripcion' => 'El estudiantado y el personal académico deben tener acceso a publicaciones periódicas especializadas y a la totalidad de la bibliografía obligatoria de la carrera.'],
                ['componente_id' => $comp('2.5'), 'nomenclatura' => '2.5.3',
                    'descripcion' => 'Los estudiantes y el personal académico de la carrera deben contar con acceso a redes de información académica: bibliotecas virtuales, bases de datos y revistas electrónicas, entre otras.'],
                ['componente_id' => $comp('2.5'), 'nomenclatura' => '2.5.4',
                    'descripcion' => 'El centro de información y recursos debe estar atendido por profesionales en el área para satisfacer las demandas de préstamo de material bibliográfico, así como la adquisición de nuevos ejemplares.'],
                ['componente_id' => $comp('2.5'), 'nomenclatura' => '2.5.5',
                    'descripcion' => 'Debe haber un presupuesto asignado para financiar la adquisición de los materiales bibliográficos requeridos por la carrera y mecanismos de coordinación con ésta, para tomar las decisiones relativas a esa adquisición.'],
                // 2.6
                ['componente_id' => $comp('2.6'), 'nomenclatura' => '2.6.1',
                    'descripcion' => 'La administración de la carrera, el personal académico, el administrativo y el técnico deben tener acceso a equipo de cómputo y multimedia adecuados y en buenas condiciones, para el desarrollo de su labor, según la naturaleza de la carrera.'],
                ['componente_id' => $comp('2.6'), 'nomenclatura' => '2.6.2',
                    'descripcion' => 'Los laboratorios de informática a los que da acceso la carrera deben contar con equipo actualizado, en cantidad suficiente, en buenas condiciones y con los recursos periféricos y de software requeridos por la carrera.'],
                ['componente_id' => $comp('2.6'), 'nomenclatura' => '2.6.3',
                    'descripcion' => 'El personal académico y el estudiantado de la carrera deben tener acceso a recursos de multimedia en buen estado, del tipo requerido y en la cantidad necesaria para el proceso formativo en el aula.'],
                ['componente_id' => $comp('2.6'), 'nomenclatura' => '2.6.4',
                    'descripcion' => 'Los laboratorios y talleres deben tener equipos especializados, en buenas condiciones y en cantidad suficiente para la labor docente y de investigación, según la naturaleza de la carrera.'],
                ['componente_id' => $comp('2.6'), 'nomenclatura' => '2.6.5',
                    'descripcion' => 'Se debe contar, en las aulas, laboratorios, talleres y espacios de trabajo, con los recursos materiales necesarios para el proceso formativo y para todas aquellas labores de gestión y apoyo que lo acompañan.'],
                // 2.7
                ['componente_id' => $comp('2.7'), 'nomenclatura' => '2.7.1',
                    'descripcion' => 'La carrera debe contar con un presupuesto suficiente que le permita cumplir sus objetivos y garantizar el mejoramiento continuo.'],
                ['componente_id' => $comp('2.7'), 'nomenclatura' => '2.7.2',
                    'descripcion' => 'Se debe contar con políticas claras que regulen la captación de recursos externos provenientes de fuentes diferentes a la principal; a saber, convenios, donaciones, cooperación, consultorías, pruebas y diagnóstico de laboratorio, investigación y otras.'],

                // ── DIMENSIÓN 3 · Proceso educativo ──────────────────────────
                // 3.1
                ['componente_id' => $comp('3.1'), 'nomenclatura' => '3.1.1',
                    'descripcion' => 'La dirección y el personal académico deben participar en la definición de las modificaciones del plan de estudios.'],
                ['componente_id' => $comp('3.1'), 'nomenclatura' => '3.1.2',
                    'descripcion' => 'El personal académico debe asistir a las diferentes actividades de coordinación que implica la gestión y desarrollo de la carrera, e involucrarse en ellas.'],
                ['componente_id' => $comp('3.1'), 'nomenclatura' => '3.1.3',
                    'descripcion' => 'La carrera debe tener personal y mecanismos administrativos que permitan verificar el cumplimiento de las responsabilidades del personal académico en lo que corresponde a dominio de la materia y de las técnicas didácticas; la actualización; la puntual asistencia a lecciones y a otras actividades curriculares; el respeto por los estudiantes; la disponibilidad ante las necesidades de formación de estos y la entrega pronta de los resultados de las evaluaciones de los aprendizajes hechas a sus alumnos.'],
                ['componente_id' => $comp('3.1'), 'nomenclatura' => '3.1.4',
                    'descripcion' => 'La carrera debe contar con el personal, los mecanismos y los instrumentos de evaluación y seguimiento del personal académico, que aseguren la calidad de su labor en cada curso y que permitan definir y aplicar acciones correctivas de mejora en forma pronta y oportuna.'],
                ['componente_id' => $comp('3.1'), 'nomenclatura' => '3.1.5',
                    'descripcion' => 'La carrera debe tener acceso a las estrategias y a un programa o proyecto institucional permanente de investigación educativa, que produzcan la innovación y la actualización de los métodos de enseñanza y la capacitación de los académicos.'],
                // 3.2
                ['componente_id' => $comp('3.2'), 'nomenclatura' => '3.2.1',
                    'descripcion' => 'Debe haber congruencia entre los objetivos del plan de estudios, el curso y el tipo de estrategia, y las actividades de aprendizaje puestas en práctica.'],
                ['componente_id' => $comp('3.2'), 'nomenclatura' => '3.2.2',
                    'descripcion' => 'Las estrategias de enseñanza y aprendizaje deben ser congruentes con la naturaleza de la carrera y la asignatura, los objetivos propuestos, las características de los estudiantes, la información, los materiales y los equipos didácticos disponibles; con el tamaño de los grupos y con las más adecuadas teorías de aprendizaje.'],
                ['componente_id' => $comp('3.2'), 'nomenclatura' => '3.2.3',
                    'descripcion' => 'La carrera debe promover en los estudiantes los aprendizajes cognitivos, el desarrollo de destrezas y la formación de actitudes positivas, así como el interés por el aprendizaje continuo y la construcción de un pensamiento crítico, creativo y autónomo.'],
                ['componente_id' => $comp('3.2'), 'nomenclatura' => '3.2.4',
                    'descripcion' => 'La carrera debe ofrecer facilidades al estudiantado para participar en giras de campo y otras actividades fuera de las instalaciones universitarias, cuando el plan de estudios lo requiera.'],
                ['componente_id' => $comp('3.2'), 'nomenclatura' => '3.2.5',
                    'descripcion' => 'La evaluación de los aprendizajes debe incluir no solo evaluación de conocimientos de acuerdo con la naturaleza de la carrera, sino también de las destrezas, las habilidades y las actitudes establecidas en el currículo y definidas en el perfil de salida.'],
                ['componente_id' => $comp('3.2'), 'nomenclatura' => '3.2.6',
                    'descripcion' => 'La propuesta de evaluación debe ser presentada y explicada a los estudiantes durante las dos primeras semanas de clase.'],
                ['componente_id' => $comp('3.2'), 'nomenclatura' => '3.2.7',
                    'descripcion' => 'Debe haber concordancia entre los métodos de enseñanza y aprendizaje y los métodos de evaluación de los aprendizajes.'],
                // 3.3
                ['componente_id' => $comp('3.3'), 'nomenclatura' => '3.3.1',
                    'descripcion' => 'Se debe contar con documentación oficial sobre la estructura organizativa y el funcionamiento de la universidad, de la unidad académica y de la carrera en particular.'],
                ['componente_id' => $comp('3.3'), 'nomenclatura' => '3.3.2',
                    'descripcion' => 'La carrera debe contar con un plan estratégico que oriente su desarrollo y su funcionamiento, el cual debe contener como mínimo: los objetivos, las acciones, los indicadores de cumplimiento y un cronograma.'],
                ['componente_id' => $comp('3.3'), 'nomenclatura' => '3.3.3',
                    'descripcion' => 'La Universidad en general y la carrera en particular deben contar con mecanismos y recursos para registrar y ofrecer estadísticas e información anual del personal académico (carga, cursos y grupos, resultados de evaluaciones, producción intelectual y otros) para uso y retroalimentación de la carrera.'],
                ['componente_id' => $comp('3.3'), 'nomenclatura' => '3.3.4',
                    'descripcion' => 'Es necesario que exista un clima de trabajo que propicie el logro de los objetivos educativos de la carrera.'],
                ['componente_id' => $comp('3.3'), 'nomenclatura' => '3.3.5',
                    'descripcion' => 'Deben existir una normativa y un procedimiento para nombrar la persona que ocupe la dirección de la carrera.'],
                ['componente_id' => $comp('3.3'), 'nomenclatura' => '3.3.6',
                    'descripcion' => 'La persona que ocupe la dirección debe reunir condiciones de idoneidad y liderazgo para el puesto.'],
                ['componente_id' => $comp('3.3'), 'nomenclatura' => '3.3.7',
                    'descripcion' => 'Se debe contar con un núcleo académico encargado de la orientación y la gestión de la carrera para que ésta no tenga un carácter unipersonal.'],
                ['componente_id' => $comp('3.3'), 'nomenclatura' => '3.3.8',
                    'descripcion' => 'La dirección de la carrera debe ejercer un control efectivo de la ejecución del plan de estudios.'],
                ['componente_id' => $comp('3.3'), 'nomenclatura' => '3.3.9',
                    'descripcion' => 'La dirección tiene la responsabilidad de informar al personal académico y a los estudiantes sobre los cambios en el plan de estudios, con anticipación a su puesta en vigencia.'],
                ['componente_id' => $comp('3.3'), 'nomenclatura' => '3.3.10',
                    'descripcion' => 'La dirección debe tener mecanismos claramente establecidos para la coordinación con otras instancias académicas vinculadas con la ejecución del plan de estudios.'],
                ['componente_id' => $comp('3.3'), 'nomenclatura' => '3.3.11',
                    'descripcion' => 'Debe contarse con mecanismos establecidos de evaluación, revisión, reflexión y actualización periódica del plan de estudios.'],
                ['componente_id' => $comp('3.3'), 'nomenclatura' => '3.3.12',
                    'descripcion' => 'Las modificaciones introducidas al plan de estudios deben estar debidamente documentadas y aprobadas según lo que corresponda.'],
                ['componente_id' => $comp('3.3'), 'nomenclatura' => '3.3.13',
                    'descripcion' => 'Deben existir espacios de reunión conjunta entre el personal académico y la dirección de manera periódica, de tal forma que posibiliten la información, la coordinación, el diálogo y la opinión sobre los aspectos académicos y administrativos de la carrera.'],
                ['componente_id' => $comp('3.3'), 'nomenclatura' => '3.3.14',
                    'descripcion' => 'Se deben realizar actividades formales periódicas con el personal académico para conocer, analizar, evaluar y tomar decisiones sobre aspectos relativos a la carrera, considerando la opinión de estudiantes, graduados y de sus empleadores.'],
                ['componente_id' => $comp('3.3'), 'nomenclatura' => '3.3.15',
                    'descripcion' => 'Se debe contar con mecanismos formales de coordinación, integración, acción conjunta y seguimiento entre el personal académico que ofrece un mismo curso, del mismo nivel o eje curricular de la carrera.'],
                ['componente_id' => $comp('3.3'), 'nomenclatura' => '3.3.16',
                    'descripcion' => 'Deben existir revisiones periódicas sobre la conveniencia para los estudiantes de la oferta de cursos y sus horarios.'],
                ['componente_id' => $comp('3.3'), 'nomenclatura' => '3.3.17',
                    'descripcion' => 'Se debe garantizar el acceso de todos los estudiantes a los cursos de la carrera.'],
                ['componente_id' => $comp('3.3'), 'nomenclatura' => '3.3.18',
                    'descripcion' => 'Deben existir políticas para el reemplazo del personal académico.'],
                ['componente_id' => $comp('3.3'), 'nomenclatura' => '3.3.19',
                    'descripcion' => 'La carrera debe contar con un programa de inducción para el personal académico y administrativo nuevo.'],
                ['componente_id' => $comp('3.3'), 'nomenclatura' => '3.3.20',
                    'descripcion' => 'Deben existir evidencias de que la carrera incorpora en su gestión el mejoramiento continuo, como parte de los resultados de su evaluación.'],
                // 3.4
                ['componente_id' => $comp('3.4'), 'nomenclatura' => '3.4.1',
                    'descripcion' => 'Existencia de políticas institucionales y de la carrera que incentiven el pensamiento científico riguroso y guíen todo lo relacionado con la realización y la utilización de investigaciones.'],
                ['componente_id' => $comp('3.4'), 'nomenclatura' => '3.4.2',
                    'descripcion' => 'La carrera debe tener estrategias claramente establecidas, y desarrollar acciones para que el personal académico esté al día en su campo de conocimiento, por medio de la consulta y la utilización de investigación reciente.'],
                ['componente_id' => $comp('3.4'), 'nomenclatura' => '3.4.3',
                    'descripcion' => 'La investigación que realiza la carrera debe ser congruente con su naturaleza.'],
                ['componente_id' => $comp('3.4'), 'nomenclatura' => '3.4.4',
                    'descripcion' => 'El personal académico que realiza actividades de investigación debe contar con los recursos requeridos para cumplir cabalmente con esa labor.'],
                ['componente_id' => $comp('3.4'), 'nomenclatura' => '3.4.5',
                    'descripcion' => 'Deben existir estrategias y disposiciones expresas dirigidas a incentivar, en el personal académico que imparte materias de la carrera, actividades que impliquen pensamiento científico riguroso, tales como investigaciones científicas, redacción de ensayos, crítica y evaluación de investigaciones científicas, entre otros.'],
                ['componente_id' => $comp('3.4'), 'nomenclatura' => '3.4.6',
                    'descripcion' => 'Deben mantenerse relaciones académicas con centros, grupos, redes o programas dedicados a la investigación en el campo disciplinar.'],
                ['componente_id' => $comp('3.4'), 'nomenclatura' => '3.4.7',
                    'descripcion' => 'Se debe fomentar la innovación en los proyectos y acciones de investigación.'],
                ['componente_id' => $comp('3.4'), 'nomenclatura' => '3.4.8',
                    'descripcion' => 'La carrera debe estimular que los resultados de sus investigaciones se integren a la práctica docente y se compartan entre académicos y estudiantes.'],
                ['componente_id' => $comp('3.4'), 'nomenclatura' => '3.4.9',
                    'descripcion' => 'Los resultados de las investigaciones o innovaciones deben ser difundidos mediante publicaciones reconocidas por la comunidad académica y otros mecanismos, para atraer la crítica y la colaboración nacional e internacional.'],
                // 3.5
                ['componente_id' => $comp('3.5'), 'nomenclatura' => '3.5.1',
                    'descripcion' => 'La carrera debe contar con políticas y procedimientos claros que favorezcan la realización de actividades de extensión.'],
                ['componente_id' => $comp('3.5'), 'nomenclatura' => '3.5.2',
                    'descripcion' => 'La carrera debe contar con lineamientos explícitos que guíen lo relacionado con la realización de proyectos de extensión.'],
                ['componente_id' => $comp('3.5'), 'nomenclatura' => '3.5.3',
                    'descripcion' => 'Existencia de acciones de extensión que proyecten la carrera sobre el entorno social.'],
                ['componente_id' => $comp('3.5'), 'nomenclatura' => '3.5.4',
                    'descripcion' => 'La carrera debe promover e incentivar entre sus académicos la realización de proyectos de extensión.'],
                ['componente_id' => $comp('3.5'), 'nomenclatura' => '3.5.5',
                    'descripcion' => 'El personal académico que realiza actividades de extensión debe contar con los recursos requeridos para cumplir cabalmente con esa labor.'],
                ['componente_id' => $comp('3.5'), 'nomenclatura' => '3.5.6',
                    'descripcion' => 'La carrera debe impulsar sus proyectos de extensión a fin de que favorezcan, promuevan y creen condiciones para construir alianzas estratégicas internas y externas, de tal manera que se potencie el trabajo conjunto y el uso de sus resultados.'],
                ['componente_id' => $comp('3.5'), 'nomenclatura' => '3.5.7',
                    'descripcion' => 'La carrera debe registrar datos cuantitativos y cualitativos de las acciones de extensión que realiza y tenerlos disponibles (por ejemplo, cantidad de beneficiarios, cantidad de actividades o productos concretos), de tal forma que la carrera cuente con información para planificar sus futuras acciones de extensión.'],
                ['componente_id' => $comp('3.5'), 'nomenclatura' => '3.5.8',
                    'descripcion' => 'Los resultados de los proyectos de extensión deben ser ampliamente difundidos entre los actores sociales involucrados, mediante las herramientas idóneas, de tal manera que se logre la apropiación del conocimiento por parte de tales actores.'],
                ['componente_id' => $comp('3.5'), 'nomenclatura' => '3.5.9',
                    'descripcion' => 'La carrera debe contar con mecanismos para evaluar el resultado de sus acciones de extensión.'],
                // 3.6
                ['componente_id' => $comp('3.6'), 'nomenclatura' => '3.6.1',
                    'descripcion' => 'La institución debe contar con políticas claras para atender integralmente a los estudiantes y fortalecer así su desarrollo cognitivo, afectivo y social.'],
                ['componente_id' => $comp('3.6'), 'nomenclatura' => '3.6.2',
                    'descripcion' => 'La universidad debe contar con normativa y procedimientos que permitan cumplir las leyes vigentes en materia de discapacidad, hostigamiento sexual y otras.'],
                ['componente_id' => $comp('3.6'), 'nomenclatura' => '3.6.3',
                    'descripcion' => 'Debe existir normativa que permita al estudiantado nombrar su asociación o elegir otro mecanismo organizativo.'],
                ['componente_id' => $comp('3.6'), 'nomenclatura' => '3.6.4',
                    'descripcion' => 'Se debe garantizar la existencia, la divulgación y el cumplimiento de la normativa estudiantil.'],
                ['componente_id' => $comp('3.6'), 'nomenclatura' => '3.6.5',
                    'descripcion' => 'Deben estar disponibles programas o servicios de orientación vocacional y ocupacional para los estudiantes que lo requieran.'],
                ['componente_id' => $comp('3.6'), 'nomenclatura' => '3.6.6',
                    'descripcion' => 'Se debe ofrecer a los estudiantes con alguna discapacidad las condiciones que garanticen su acceso a las diversas acciones educativas de la carrera.'],
                ['componente_id' => $comp('3.6'), 'nomenclatura' => '3.6.7',
                    'descripcion' => 'La institución debe garantizar a los estudiantes, para todos los efectos, el registro, control y certificación de todos sus datos académicos, así como implementar las medidas de seguridad pertinentes para garantizar la veracidad de la información y proteger su confidencialidad.'],
                ['componente_id' => $comp('3.6'), 'nomenclatura' => '3.6.8',
                    'descripcion' => 'Se debe contar con servicios para que los estudiantes tengan acceso a becas, ayudas económicas o financiamiento, por su condición socioeconómica, por su excelencia académica o por su participación en actividades universitarias.'],
                ['componente_id' => $comp('3.6'), 'nomenclatura' => '3.6.9',
                    'descripcion' => 'Se debe contar con información sobre el uso y la calidad de los servicios académicos y estudiantiles, y utilizar esta información para la toma de decisiones y el mejoramiento de éstos.'],
                ['componente_id' => $comp('3.6'), 'nomenclatura' => '3.6.10',
                    'descripcion' => 'Las dependencias administrativas y los servicios académicos deben tener un horario acorde con las necesidades del estudiantado.'],
                ['componente_id' => $comp('3.6'), 'nomenclatura' => '3.6.11',
                    'descripcion' => 'Se debe ofrecer al estudiantado una guía sobre los procesos administrativos fundamentales.'],
                ['componente_id' => $comp('3.6'), 'nomenclatura' => '3.6.12',
                    'descripcion' => 'Debe contarse con medios eficientes que permitan al estudiantado expresar sus opiniones o su nivel de satisfacción con respecto a la carrera, al personal académico, a los servicios y a las actividades que brinda la institución.'],
                ['componente_id' => $comp('3.6'), 'nomenclatura' => '3.6.13',
                    'descripcion' => 'Debe haber acceso de los estudiantes a las instancias de la carrera donde se analicen y decidan asuntos de interés estudiantil, de acuerdo con la normativa institucional.'],
                ['componente_id' => $comp('3.6'), 'nomenclatura' => '3.6.14',
                    'descripcion' => 'Debe existir asesoría académica curricular para el estudiantado.'],
                ['componente_id' => $comp('3.6'), 'nomenclatura' => '3.6.15',
                    'descripcion' => 'La unidad académica debe ofrecer al estudiantado tiempo de consulta extra-clase sobre la materia de cada curso.'],
                ['componente_id' => $comp('3.6'), 'nomenclatura' => '3.6.16',
                    'descripcion' => 'Deben existir instancias que den seguimiento al desempeño del estudiante y remitan los problemas detectados a las personas encargadas de los servicios de apoyo existentes.'],
                ['componente_id' => $comp('3.6'), 'nomenclatura' => '3.6.17',
                    'descripcion' => 'Deben desarrollarse acciones de inducción para nuevos estudiantes, que favorezcan su proceso de transición de un nivel educativo a otro.'],

                // ── DIMENSIÓN 4 · Resultados ──────────────────────────────────
                // 4.1
                ['componente_id' => $comp('4.1'), 'nomenclatura' => '4.1.1',
                    'descripcion' => 'Debe existir un reglamento de evaluación de los aprendizajes que estipule aspectos como la escala de calificación, las normas de evaluación, los mecanismos y los plazos de apelación, y todo lo relacionado con el rendimiento académico obtenido por el estudiante en los cursos y en la carrera.'],
                ['componente_id' => $comp('4.1'), 'nomenclatura' => '4.1.2',
                    'descripcion' => 'La Universidad en general y la carrera en particular deben contar con mecanismos y recursos para registrar y ofrecer estadísticas e información anual del estudiantado (matrícula, características sociodemográficas, admisión, rendimiento académico y tiempo promedio de graduación, entre otras) para uso y retroalimentación de la carrera.'],
                ['componente_id' => $comp('4.1'), 'nomenclatura' => '4.1.3',
                    'descripcion' => 'Disponibilidad de información confiable sobre el rendimiento académico por curso, por docente y por nivel de la carrera.'],
                ['componente_id' => $comp('4.1'), 'nomenclatura' => '4.1.4',
                    'descripcion' => 'Se debe garantizar a los estudiantes el acceso oportuno a los resultados de su rendimiento en cada uno de los cursos matriculados.'],
                ['componente_id' => $comp('4.1'), 'nomenclatura' => '4.1.5',
                    'descripcion' => 'Deben existir acciones educativas que promuevan y procuren el éxito académico de la mayoría de los estudiantes.'],
                // 4.2
                ['componente_id' => $comp('4.2'), 'nomenclatura' => '4.2.1',
                    'descripcion' => 'La carrera debe disponer de información confiable que permita establecer el nivel de cumplimiento del estudiantado en cuanto a los requisitos de graduación autorizados.'],
                ['componente_id' => $comp('4.2'), 'nomenclatura' => '4.2.2',
                    'descripcion' => 'Las razones principales que explican una prolongación mayor en el período de estudios y graduación deberán estar relacionadas con situaciones atribuibles a los estudiantes o a su entorno y no a la carrera.'],
                ['componente_id' => $comp('4.2'), 'nomenclatura' => '4.2.3',
                    'descripcion' => 'La carrera debe contar con acciones de proyección en las que participen estudiantes y personal académico.'],
                ['componente_id' => $comp('4.2'), 'nomenclatura' => '4.2.4',
                    'descripcion' => 'La carrera debe contar con información actualizada sobre las condiciones del mercado laboral de la disciplina y sobre la inserción laboral de sus graduados, e informarlo a los estudiantes.'],
                ['componente_id' => $comp('4.2'), 'nomenclatura' => '4.2.5',
                    'descripcion' => 'La carrera debe contar con un sistema de información sobre sus graduados.'],
                ['componente_id' => $comp('4.2'), 'nomenclatura' => '4.2.6',
                    'descripcion' => 'Deberá demostrarse que la carrera da seguimiento a sus graduados y que utiliza la información para introducir mejoras en el plan de estudios.'],
                ['componente_id' => $comp('4.2'), 'nomenclatura' => '4.2.7',
                    'descripcion' => 'La carrera debe contar con mecanismos para conocer la percepción de los graduados sobre la formación recibida.'],
                ['componente_id' => $comp('4.2'), 'nomenclatura' => '4.2.8',
                    'descripcion' => 'Un alto porcentaje de graduados debe mostrarse satisfecho con la formación recibida.'],
                ['componente_id' => $comp('4.2'), 'nomenclatura' => '4.2.9',
                    'descripcion' => 'Se deben desarrollar acciones que permitan mantener el vínculo de los graduados con actividades de la carrera.'],
                ['componente_id' => $comp('4.2'), 'nomenclatura' => '4.2.10',
                    'descripcion' => 'La carrera debe ofrecer a sus graduados oportunidades de actualización profesional.'],
                ['componente_id' => $comp('4.2'), 'nomenclatura' => '4.2.11',
                    'descripcion' => 'Un alto porcentaje de empleadores debe mostrarse satisfecho con los graduados.'],
                // 4.3
                ['componente_id' => $comp('4.3'), 'nomenclatura' => '4.3.1',
                    'descripcion' => 'El personal docente debe contar con producción académica proveniente de su trabajo de investigación y extensión al interior de la carrera.'],

                // ── SOSTENIBILIDAD ────────────────────────────────────────────
                ['componente_id' => $comp('S'), 'nomenclatura' => 'S.1',
                    'descripcion' => 'La universidad cuenta con políticas, mecanismos y lineamientos aprobados y en ejecución que facilitan la realización del proceso de autoevaluación institucional.'],
                ['componente_id' => $comp('S'), 'nomenclatura' => 'S.2',
                    'descripcion' => 'La universidad cuenta con políticas, mecanismos y lineamientos que facilitan la elaboración y ejecución del compromiso de mejoramiento.'],
                ['componente_id' => $comp('S'), 'nomenclatura' => 'S.3',
                    'descripcion' => 'La universidad cuenta con políticas, mecanismos y lineamientos que garantizan el monitoreo y el seguimiento de los procesos de autoevaluación.'],
                ['componente_id' => $comp('S'), 'nomenclatura' => 'S.4',
                    'descripcion' => 'La universidad cuenta con políticas, mecanismos y lineamientos que garantizan el monitoreo y el seguimiento de la ejecución de los compromisos de mejoramiento.'],
                ['componente_id' => $comp('S'), 'nomenclatura' => 'S.5',
                    'descripcion' => 'La universidad cuenta con políticas, mecanismos y lineamientos que garantizan el desarrollo de una cultura de evaluación y gestión de la calidad.'],
                ['componente_id' => $comp('S'), 'nomenclatura' => 'S.6',
                    'descripcion' => 'La carrera cuenta con políticas, mecanismos y lineamientos que facilitan la realización del proceso de autoevaluación.'],
                ['componente_id' => $comp('S'), 'nomenclatura' => 'S.7',
                    'descripcion' => 'La carrera cuenta con políticas, mecanismos y lineamientos que facilitan la elaboración y ejecución del compromiso de mejoramiento.'],
                ['componente_id' => $comp('S'), 'nomenclatura' => 'S.8',
                    'descripcion' => 'La carrera cuenta con políticas, mecanismos y lineamientos que garantizan el monitoreo y el seguimiento de los procesos de autoevaluación.'],
                ['componente_id' => $comp('S'), 'nomenclatura' => 'S.9',
                    'descripcion' => 'La carrera cuenta con políticas, mecanismos y lineamientos que garantizan el monitoreo y el seguimiento de la ejecución de los compromisos de mejoramiento.'],
                ['componente_id' => $comp('S'), 'nomenclatura' => 'S.10',
                    'descripcion' => 'La carrera cuenta con políticas, mecanismos y lineamientos que garantizan el desarrollo de una cultura de evaluación y gestión de la calidad.'],
            ];

            foreach ($criterios as &$c) {
                $c['activo'] = true;
                $c['created_at'] = now();
                $c['updated_at'] = now();
            }
            unset($c);
            DB::table('CRITERIO')->insert($criterios);
            $this->command->info('  ✓ 171 Criterios');
        } else {
            $this->command->warn('  ℹ  CRITERIO ya tiene datos — omitiendo inserción.');
        }

        $crit = fn (string $n) => DB::table('CRITERIO')->where('nomenclatura', $n)->value('criterio_id');

        // ═══════════════════════════════════════════════════════════════════════
        // ESTÁNDARES  (34 en total — numerados según Cuadro No. 2 del manual)
        // ═══════════════════════════════════════════════════════════════════════
        if (DB::table('ESTANDAR')->count() === 0) {
            DB::table('ESTANDAR')->insert([
                // Estándar 1 — criterio 1.1.1
                ['criterio_id' => $crit('1.1.1'),
                    'descripcion' => 'La carrera debe contar al menos con un material informativo.',
                    'activo' => true, 'created_at' => now(), 'updated_at' => now()],
                // Estándar 2 — criterio 1.1.2
                ['criterio_id' => $crit('1.1.2'),
                    'descripcion' => 'Al menos un 70% de los estudiantes debe reportar que recibe la información necesaria para su vida académica.',
                    'activo' => true, 'created_at' => now(), 'updated_at' => now()],
                // Estándar 3 — criterio 1.3.4
                ['criterio_id' => $crit('1.3.4'),
                    'descripcion' => 'Se debe contar con al menos un convenio y relaciones de coordinación académica –nacional o internacional– con otras disciplinas o unidades académicas, que favorezcan el intercambio de experiencias entre profesores y estudiantes, así como la realización conjunta de acciones que tengan relevancia e incidencia positiva en la carrera.',
                    'activo' => true, 'created_at' => now(), 'updated_at' => now()],
                // Estándar 4 — criterio 2.1.17
                ['criterio_id' => $crit('2.1.17'),
                    'descripcion' => 'Todos los cursos —el 100%— deben contar con sus respectivos programas y éstos deben estar completos.',
                    'activo' => true, 'created_at' => now(), 'updated_at' => now()],
                // Estándar 5 — criterio 2.2.4
                ['criterio_id' => $crit('2.2.4'),
                    'descripcion' => 'El 100% del personal académico deberá poseer como mínimo el grado de Licenciatura. (Cuando este estándar no se cumpla en su totalidad, deberán indicarse las razones).',
                    'activo' => true, 'created_at' => now(), 'updated_at' => now()],
                // Estándar 6 — criterio 2.2.4
                ['criterio_id' => $crit('2.2.4'),
                    'descripcion' => 'Al menos un 25% del personal académico asociado a asignaturas propias de la carrera, debe contar con grados superiores a la licenciatura.',
                    'activo' => true, 'created_at' => now(), 'updated_at' => now()],
                // Estándar 7 — criterio 2.2.4
                ['criterio_id' => $crit('2.2.4'),
                    'descripcion' => 'Al menos un 50% del personal académico de cursos propios de la carrera, debe tener como mínimo tres años de experiencia académica universitaria.',
                    'activo' => true, 'created_at' => now(), 'updated_at' => now()],
                // Estándar 8 — criterio 2.2.4
                ['criterio_id' => $crit('2.2.4'),
                    'descripcion' => 'Al menos un 30% del personal académico debe tener como mínimo tres años de experiencia profesional, según la naturaleza de la carrera.',
                    'activo' => true, 'created_at' => now(), 'updated_at' => now()],
                // Estándar 9 — criterio 2.2.5
                ['criterio_id' => $crit('2.2.5'),
                    'descripcion' => 'Al menos un 25% del personal académico deberá haberse graduado, en el nivel de grado o de posgrado, en otras instituciones universitarias nacionales o extranjeras.',
                    'activo' => true, 'created_at' => now(), 'updated_at' => now()],
                // Estándar 10 — criterio 2.2.7
                ['criterio_id' => $crit('2.2.7'),
                    'descripcion' => 'Al menos un 70% del personal académico debe tener una relación contractual estable.',
                    'activo' => true, 'created_at' => now(), 'updated_at' => now()],
                // Estándar 11 — criterio 2.2.8
                ['criterio_id' => $crit('2.2.8'),
                    'descripcion' => 'Al menos el 70% del personal académico de la carrera deberá haber participado en procesos de capacitación o actualización relacionados con la competencia docente (planeamiento, metodología, recursos para el aprendizaje, evaluación y otros).',
                    'activo' => true, 'created_at' => now(), 'updated_at' => now()],
                // Estándar 12 — criterio 2.4.4
                ['criterio_id' => $crit('2.4.4'),
                    'descripcion' => 'Al menos un 80% de personal académico, administrativo, técnico, de apoyo y estudiantes, deben opinar que cuentan con buenas condiciones de higiene, seguridad y salud ocupacional en todos los tipos de planta física que utiliza la carrera.',
                    'activo' => true, 'created_at' => now(), 'updated_at' => now()],
                // Estándar 13 — criterio 2.5.1
                ['criterio_id' => $crit('2.5.1'),
                    'descripcion' => 'Al menos un 70% del personal académico y de los estudiantes debe mostrarse satisfecho con los diferentes aspectos del centro de información y recursos.',
                    'activo' => true, 'created_at' => now(), 'updated_at' => now()],
                // Estándar 14 — criterio 2.5.2
                ['criterio_id' => $crit('2.5.2'),
                    'descripcion' => 'El centro de información y recursos al que accede la carrera debe contar, al menos, con un ejemplar de cada uno de los libros o documentos que incluye la bibliografía obligatoria de los programas de los cursos; con un ejemplar, al menos, de cada uno de los trabajos que ha producido el personal académico, así como de los informes finales de proyectos académicos.',
                    'activo' => true, 'created_at' => now(), 'updated_at' => now()],
                // Estándar 15 — criterio 2.6.1
                ['criterio_id' => $crit('2.6.1'),
                    'descripcion' => 'Al menos un 70% del personal académico, del administrativo y del técnico deben reportar satisfacción con el estado del equipo de cómputo y multimedia, y con su acceso a éste.',
                    'activo' => true, 'created_at' => now(), 'updated_at' => now()],
                // Estándar 16 — criterio 2.6.2
                ['criterio_id' => $crit('2.6.2'),
                    'descripcion' => 'El 100% de los estudiantes debe tener acceso pleno al laboratorio de informática.',
                    'activo' => true, 'created_at' => now(), 'updated_at' => now()],
                // Estándar 17 — criterio 3.1.4
                ['criterio_id' => $crit('3.1.4'),
                    'descripcion' => 'Los instrumentos de evaluación del personal académico serán administrados a todos los estudiantes y en todos los cursos.',
                    'activo' => true, 'created_at' => now(), 'updated_at' => now()],
                // Estándar 18 — criterio 3.2.6
                ['criterio_id' => $crit('3.2.6'),
                    'descripcion' => 'Durante las dos primeras semanas de clase, el 100% de los estudiantes de cada curso debe ser informado sobre la propuesta de evaluación y recibir una clara explicación acerca de ésta.',
                    'activo' => true, 'created_at' => now(), 'updated_at' => now()],
                // Estándar 19 — criterio 3.3.4
                ['criterio_id' => $crit('3.3.4'),
                    'descripcion' => 'Al menos un 70% del personal que labora en la carrera debe reportar la existencia de un clima de trabajo que propicie el logro de los objetivos educativos de la carrera.',
                    'activo' => true, 'created_at' => now(), 'updated_at' => now()],
                // Estándar 20 — criterio 3.3.5
                ['criterio_id' => $crit('3.3.5'),
                    'descripcion' => 'La persona que ocupe la dirección de la carrera debe dedicar al menos medio tiempo a las labores de dirección.',
                    'activo' => true, 'created_at' => now(), 'updated_at' => now()],
                // Estándar 21 — criterio 3.3.17
                ['criterio_id' => $crit('3.3.17'),
                    'descripcion' => 'El 100% de los cursos debe ofrecerse de acuerdo con la programación establecida en el plan de estudios, al menos una vez al año.',
                    'activo' => true, 'created_at' => now(), 'updated_at' => now()],
                // Estándar 22 — criterio 3.4.5
                ['criterio_id' => $crit('3.4.5'),
                    'descripcion' => 'La carrera debe ejecutar al menos un proyecto de investigación en áreas propias de su disciplina.',
                    'activo' => true, 'created_at' => now(), 'updated_at' => now()],
                // Estándar 23 — criterio 3.5.3
                ['criterio_id' => $crit('3.5.3'),
                    'descripcion' => 'La carrera debe ejecutar, al menos, un proyecto de extensión por año.',
                    'activo' => true, 'created_at' => now(), 'updated_at' => now()],
                // Estándar 24 — criterio 3.6.4
                ['criterio_id' => $crit('3.6.4'),
                    'descripcion' => 'Se debe contar, al menos, con un reglamento de régimen estudiantil o con normativa que regule los deberes y derechos, los aspectos disciplinarios, la libertad de organización, la representación y la participación, así como la revisión de las decisiones que afecten a los estudiantes.',
                    'activo' => true, 'created_at' => now(), 'updated_at' => now()],
                // Estándar 25 — criterio 3.6.10
                ['criterio_id' => $crit('3.6.10'),
                    'descripcion' => 'Al menos un 70% de los estudiantes se deben mostrar satisfechos con el horario de los servicios ofrecidos por las dependencias administrativas y con los servicios académicos.',
                    'activo' => true, 'created_at' => now(), 'updated_at' => now()],
                // Estándar 26 — criterio 3.6.14
                ['criterio_id' => $crit('3.6.14'),
                    'descripcion' => 'La carrera debe contar con dos años de experiencia, al menos, en el ofrecimiento de asesoría académica curricular.',
                    'activo' => true, 'created_at' => now(), 'updated_at' => now()],
                // Estándar 27 — criterio 4.2.1
                ['criterio_id' => $crit('4.2.1'),
                    'descripcion' => 'Todos los estudiantes, para obtener el grado de licenciatura, deben realizar un trabajo final de graduación o su equivalente, de acuerdo con la naturaleza de la carrera.',
                    'activo' => true, 'created_at' => now(), 'updated_at' => now()],
                // Estándar 28 — criterio 4.2.3
                ['criterio_id' => $crit('4.2.3'),
                    'descripcion' => 'Todos los estudiantes, antes de graduarse, deben cumplir con al menos 150 horas de Trabajo Comunal, o su equivalente en acciones de proyección relacionadas estrechamente con la carrera.',
                    'activo' => true, 'created_at' => now(), 'updated_at' => now()],
                // Estándar 29 — criterio 4.2.7
                ['criterio_id' => $crit('4.2.7'),
                    'descripcion' => 'Al menos un 70% de una muestra representativa de graduados de los últimos cuatro años opina que la carrera lo facultó para continuar aprendiendo en el campo de su especialidad.',
                    'activo' => true, 'created_at' => now(), 'updated_at' => now()],
                // Estándar 30 — criterio 4.2.8
                ['criterio_id' => $crit('4.2.8'),
                    'descripcion' => 'Al menos un 70% de una muestra representativa de graduados debe opinar que la preparación recibida durante la carrera le permite desempeñarse satisfactoriamente en su trabajo.',
                    'activo' => true, 'created_at' => now(), 'updated_at' => now()],
                // Estándar 31 — criterio 4.2.11
                ['criterio_id' => $crit('4.2.11'),
                    'descripcion' => 'Al menos un 70% de una muestra representativa de empleadores de graduados de la carrera han de mostrarse satisfechos con el desempeño y con el perfil profesional de salida de los graduados de la carrera.',
                    'activo' => true, 'created_at' => now(), 'updated_at' => now()],
                // Estándar 32 — criterio 4.3.1
                ['criterio_id' => $crit('4.3.1'),
                    'descripcion' => 'Al menos un 50% del personal académico a tiempo completo debe contar con producción académica indexada anualmente.',
                    'activo' => true, 'created_at' => now(), 'updated_at' => now()],
                // Estándar 33 — criterio 4.3.1
                ['criterio_id' => $crit('4.3.1'),
                    'descripcion' => 'Al menos un 25% del personal a tiempo parcial debe contar con producción académica indexada anualmente.',
                    'activo' => true, 'created_at' => now(), 'updated_at' => now()],
                // Estándar 34 — criterio 4.3.1
                ['criterio_id' => $crit('4.3.1'),
                    'descripcion' => 'Al menos cada dos años, el personal académico de la carrera a tiempo completo debe participar con ponencias o conferencias en foros nacionales o internacionales.',
                    'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ]);
            $this->command->info('  ✓ 34 Estándares');
        } else {
            $this->command->warn('  ℹ  ESTANDAR ya tiene datos — omitiendo inserción.');
        }

        // ═══════════════════════════════════════════════════════════════════════
        // EVIDENCIAS  (348 en total — textos corregidos contra el PDF)
        // ═══════════════════════════════════════════════════════════════════════
        if (DB::table('EVIDENCIA')->count() === 0) {
            $evidencias = [
                // ── ADMISIBILIDAD ────────────────────────────────────────────
                ['criterio_id' => $crit('A.1'),  'nomenclatura' => '1',   'descripcion' => 'Total de años de funcionamiento efectivo de la carrera o programa.'],
                ['criterio_id' => $crit('A.1'),  'nomenclatura' => '2',   'descripcion' => 'Año de graduación del primer grupo de estudiantes admitido en la carrera o programa.'],
                ['criterio_id' => $crit('A.1'),  'nomenclatura' => '3',   'descripcion' => 'Serie histórica de las matrículas y de los graduados de los últimos cinco años.'],
                ['criterio_id' => $crit('A.2'),  'nomenclatura' => '4',   'descripcion' => 'Lista de cursos, con indicación del número de créditos de cada uno, horas semanales de lecciones, horas semanales de trabajo del estudiante y semanas durante las cuales se imparte.'],
                ['criterio_id' => $crit('A.3'),  'nomenclatura' => '5',   'descripcion' => 'Duración del ciclo lectivo en semanas.'],
                ['criterio_id' => $crit('A.3'),  'nomenclatura' => '6',   'descripcion' => 'Justificación de la duración del ciclo y los créditos cuando la duración sea diferente, con indicación del número de horas y de créditos de los cursos lectivos de la malla curricular.'],
                ['criterio_id' => $crit('A.4'),  'nomenclatura' => '7',   'descripcion' => 'Descripción del grado de cumplimiento con la normativa de grados y títulos de la educación superior en Costa Rica.'],
                ['criterio_id' => $crit('A.5'),  'nomenclatura' => '8',   'descripcion' => 'Lista de estudiantes procedentes de otras universidades a quienes se les reconocieron o equipararon cursos, según universidad, carrera de procedencia y cursos reconocidos.'],
                ['criterio_id' => $crit('A.6'),  'nomenclatura' => '9',   'descripcion' => 'Descripción del mecanismo que se sigue en la carrera para resolver las solicitudes de reconocimiento, y normativa que rige la decisión.'],
                ['criterio_id' => $crit('A.6'),  'nomenclatura' => '10',  'descripcion' => 'Lista de cursos reconocidos y no reconocidos, según institución de procedencia.'],
                ['criterio_id' => $crit('A.7'),  'nomenclatura' => '11',  'descripcion' => 'Porcentaje máximo de créditos que se puede equiparar al estudiante procedente de otra universidad (según normativa institucional).'],
                ['criterio_id' => $crit('A.7'),  'nomenclatura' => '12',  'descripcion' => 'Distribución de los estudiantes de la carrera a quienes se les han equiparado créditos, según porcentaje de créditos reconocidos con respecto al total de créditos del plan de estudios.'],
                ['criterio_id' => $crit('A.8'),  'nomenclatura' => '13',  'descripcion' => 'Certificación, emitida por la instancia universitaria competente, de que el 100% de los estudiantes procedentes de la secundaria cumple con la presentación del título de Bachiller de Enseñanza Media debidamente certificado.'],
                ['criterio_id' => $crit('A.9'),  'nomenclatura' => '14',  'descripcion' => 'Total de estudiantes a los que se les ha reconocido un título en los últimos cuatro años, según institución de procedencia.'],
                ['criterio_id' => $crit('A.10'), 'nomenclatura' => '15',  'descripcion' => 'Lista de convenios existentes con instituciones parauniversitarias.'],
                ['criterio_id' => $crit('A.10'), 'nomenclatura' => '16',  'descripcion' => 'Total de estudiantes a los que se les ha reconocido un tramo de carrera de una institución parauniversitaria en los últimos cuatro años, según procedencia.'],
                ['criterio_id' => $crit('A.11'), 'nomenclatura' => '17',  'descripcion' => 'Lista de cursos equiparados de otras universidades, según grado académico al que pertenecían en la universidad de procedencia, comparado con el grado académico de la carrera para la cual se reconocieron.'],
                ['criterio_id' => $crit('A.12'), 'nomenclatura' => '18',  'descripcion' => 'Descripción de la normativa que regula el reconocimiento de aprendizajes por experiencia.'],
                ['criterio_id' => $crit('A.12'), 'nomenclatura' => '19',  'descripcion' => 'Número de estudiantes a quienes se han reconocido aprendizajes por experiencia, y porcentaje de cursos del plan de estudios que tales aprendizajes sustituyen, por cada estudiante.'],

                // ── DIMENSIÓN 1 ───────────────────────────────────────────────
                // 1.1.1
                ['criterio_id' => $crit('1.1.1'), 'nomenclatura' => '20', 'descripcion' => 'Lista descriptiva de los materiales informativos disponibles (impresos, audiovisuales o electrónicos) según temas que trata cada uno y medios de publicación (impresos, Internet, prensa, televisión, actividades de divulgación como ferias y otros). Debe haber muestras disponibles.'],
                ['criterio_id' => $crit('1.1.1'), 'nomenclatura' => '21', 'descripcion' => 'Descripción de la estrategia de comunicación y divulgación de los materiales.'],
                // 1.1.2
                ['criterio_id' => $crit('1.1.2'), 'nomenclatura' => '22', 'descripcion' => 'Porcentaje de estudiantes que, según reportan, reciben la información requerida, por temas.'],
                ['criterio_id' => $crit('1.1.2'), 'nomenclatura' => '23', 'descripcion' => 'Porcentaje de estudiantes que opina que la entrega de estos materiales es oportuna y que la información contenida en ellos es veraz.'],
                // 1.2.1
                ['criterio_id' => $crit('1.2.1'), 'nomenclatura' => '24', 'descripcion' => 'Normativa y lista de trámites y requisitos de ingreso, para estudiantes procedentes de la secundaria o de otras universidades o carreras.'],
                ['criterio_id' => $crit('1.2.1'), 'nomenclatura' => '25', 'descripcion' => 'Medios de difusión de trámites y requisitos de ingreso.'],
                // 1.2.2
                ['criterio_id' => $crit('1.2.2'), 'nomenclatura' => '26', 'descripcion' => 'Descripción de las políticas y mecanismos institucionales que garantizan el acceso de estudiantes en igualdad de oportunidades.'],
                ['criterio_id' => $crit('1.2.2'), 'nomenclatura' => '27', 'descripcion' => 'Distribución de los estudiantes admitidos en los últimos cuatro años según sexo, nacionalidad, edad, condición de discapacidad (si la presentan) e institución educativa de procedencia.'],
                ['criterio_id' => $crit('1.2.2'), 'nomenclatura' => '28', 'descripcion' => 'Descripción de las condiciones de la infraestructura, de los materiales, del equipo y humanas que permiten el acceso, en igualdad de oportunidades, a personas con discapacidad.'],
                // 1.3.1
                ['criterio_id' => $crit('1.3.1'), 'nomenclatura' => '29', 'descripcion' => 'Descripción que justifique que el plan de estudios responde al estado del arte de la disciplina y a la realidad del contexto nacional e internacional, así como al mercado laboral.'],
                // 1.3.2
                ['criterio_id' => $crit('1.3.2'), 'nomenclatura' => '30', 'descripcion' => 'Descripción de las políticas correspondientes a la participación de estudiantes en la atención de necesidades del contexto.'],
                ['criterio_id' => $crit('1.3.2'), 'nomenclatura' => '31', 'descripcion' => 'Total de estudiantes que informa haber realizado acciones para atender necesidades del contexto, como parte de sus actividades académicas, y acciones que se reportan.'],
                // 1.3.3
                ['criterio_id' => $crit('1.3.3'), 'nomenclatura' => '32', 'descripcion' => 'Descripción de actividades o contenidos de los cursos del plan de estudios que reflejan el análisis de problemas del contexto, y propuestas de solución planteadas por la carrera.'],
                // 1.3.4
                ['criterio_id' => $crit('1.3.4'), 'nomenclatura' => '33', 'descripcion' => 'Lista y descripción de convenios y redes académicas vigentes, nacionales e internacionales, que benefician la carrera.'],
                ['criterio_id' => $crit('1.3.4'), 'nomenclatura' => '34', 'descripcion' => 'Descripción de acciones académicas, resultados y perspectivas de los convenios de coordinación.'],
                ['criterio_id' => $crit('1.3.4'), 'nomenclatura' => '35', 'descripcion' => 'Total de estudiantes y de profesores que participan en la realización conjunta de acciones académicas.'],
                // 1.3.5
                ['criterio_id' => $crit('1.3.5'), 'nomenclatura' => '36', 'descripcion' => 'Lista de empresas o instituciones con las que se coordinan experiencias prácticas del estudiantado.'],
                ['criterio_id' => $crit('1.3.5'), 'nomenclatura' => '37', 'descripcion' => 'Enumeración de las experiencias, con indicación del número y el tipo de participantes, así como los logros alcanzados.'],
                // 1.3.6
                ['criterio_id' => $crit('1.3.6'), 'nomenclatura' => '38', 'descripcion' => 'Porcentaje de empleadores que opina que la preparación recibida permite, a los graduados, enfrentar con éxito los cambios del contexto social y laboral de su disciplina y de la disciplina en sí misma.'],
                ['criterio_id' => $crit('1.3.6'), 'nomenclatura' => '39', 'descripcion' => 'Percepción de los graduados sobre su grado de preparación para enfrentar con éxito los cambios del contexto social y laboral de su disciplina y de la disciplina en sí misma.'],

                // ── DIMENSIÓN 2 ───────────────────────────────────────────────
                // 2.1.1
                ['criterio_id' => $crit('2.1.1'), 'nomenclatura' => '40', 'descripcion' => 'Existencia de un documento oficial de la carrera que incluya sus antecedentes, los fundamentos conceptuales, los objetivos, los fines, los ejes curriculares y la orientación metodológica.'],
                ['criterio_id' => $crit('2.1.1'), 'nomenclatura' => '41', 'descripcion' => 'Descripción de los medios utilizados para la divulgación de este documento.'],
                // 2.1.2
                ['criterio_id' => $crit('2.1.2'), 'nomenclatura' => '42', 'descripcion' => 'Justificación de la congruencia de los fines y objetivos de la carrera con los postulados de la institución.'],
                ['criterio_id' => $crit('2.1.2'), 'nomenclatura' => '43', 'descripcion' => 'Porcentaje del personal académico que considera que los fines y objetivos de la carrera guían el proceso educativo.'],
                // 2.1.3
                ['criterio_id' => $crit('2.1.3'), 'nomenclatura' => '44', 'descripcion' => 'Existencia de un documento oficial que incluya los referentes universales y las corrientes de pensamiento que fundamentan la carrera, así como el lugar en que se encuentran estipulados.'],
                ['criterio_id' => $crit('2.1.3'), 'nomenclatura' => '45', 'descripcion' => 'Porcentaje del personal académico que considera pertinentes y actuales los referentes universales.'],
                // 2.1.4
                ['criterio_id' => $crit('2.1.4'), 'nomenclatura' => '46', 'descripcion' => 'Documento en que se especifica el perfil de entrada, con los conocimientos, habilidades y actitudes establecidos.'],
                ['criterio_id' => $crit('2.1.4'), 'nomenclatura' => '47', 'descripcion' => 'Porcentajes del personal académico y de los estudiantes que considera que hay congruencia entre el perfil de entrada y las habilidades, las actitudes y los conocimientos requeridos.'],
                // 2.1.5
                ['criterio_id' => $crit('2.1.5'), 'nomenclatura' => '48', 'descripcion' => 'Documento en el que se describe un perfil profesional de salida específico para la carrera, con los conocimientos, habilidades y actitudes correspondientes.'],
                ['criterio_id' => $crit('2.1.5'), 'nomenclatura' => '49', 'descripcion' => 'Porcentajes de personal académico, empleadores y graduados que opinan que hay congruencia entre el perfil profesional de salida y el ejercicio de la profesión.'],
                // 2.1.6
                ['criterio_id' => $crit('2.1.6'), 'nomenclatura' => '50', 'descripcion' => 'Existencia de una malla curricular con la duración de la carrera en años y ciclos.'],
                ['criterio_id' => $crit('2.1.6'), 'nomenclatura' => '51', 'descripcion' => 'Porcentajes de personal académico, estudiantes y graduados que consideran pertinente la secuencia de los cursos en el plan de estudios.'],
                ['criterio_id' => $crit('2.1.6'), 'nomenclatura' => '52', 'descripcion' => 'Descripción de los mecanismos que aseguran el cumplimiento de los requisitos y correquisitos de cada curso.'],
                // 2.1.7
                ['criterio_id' => $crit('2.1.7'), 'nomenclatura' => '53', 'descripcion' => 'Justificación de la distribución porcentual de los cursos del plan de estudios, según sean teóricos, prácticos o teórico-prácticos.'],
                // 2.1.8
                ['criterio_id' => $crit('2.1.8'), 'nomenclatura' => '54', 'descripcion' => 'Descripción de los mecanismos generales de la carrera que, según la naturaleza de ésta, se siguen para integrar la teoría y la práctica.'],
                ['criterio_id' => $crit('2.1.8'), 'nomenclatura' => '55', 'descripcion' => 'Porcentaje de estudiantes que opina que existe integración teórico-práctica en los cursos.'],
                // 2.1.9
                ['criterio_id' => $crit('2.1.9'), 'nomenclatura' => '56', 'descripcion' => 'Lista de contenidos, temas, actividades académicas u otras acciones que posibilitan una perspectiva multidisciplinaria del plan de estudios.'],
                // 2.1.10
                ['criterio_id' => $crit('2.1.10'), 'nomenclatura' => '57', 'descripcion' => 'Explicación de cómo la ética se incorpora en el plan de estudios.'],
                ['criterio_id' => $crit('2.1.10'), 'nomenclatura' => '58', 'descripcion' => 'Porcentaje de estudiantes que opina que sí se desarrollan contenidos de ética para el ejercicio profesional en cursos de la carrera.'],
                // 2.1.11
                ['criterio_id' => $crit('2.1.11'), 'nomenclatura' => '59', 'descripcion' => 'Porcentaje de estudiantes que indica la realización de lecturas o estudio en otro idioma durante la carrera.'],
                // 2.1.12
                ['criterio_id' => $crit('2.1.12'), 'nomenclatura' => '60', 'descripcion' => 'Descripción de las tecnologías de información disponibles y las necesarias para la carrera, de acuerdo con la naturaleza de ésta.'],
                ['criterio_id' => $crit('2.1.12'), 'nomenclatura' => '61', 'descripcion' => 'Porcentaje de estudiantes y profesores que opinan que, en los cursos, se utilizan con frecuencia y pertinencia las tecnologías de información.'],
                // 2.1.13
                ['criterio_id' => $crit('2.1.13'), 'nomenclatura' => '62', 'descripcion' => 'Explicación de cómo el plan de estudios desarrolla en los estudiantes principios y prácticas científicas relevantes para la disciplina, según cursos en los que se desarrollan.'],
                ['criterio_id' => $crit('2.1.13'), 'nomenclatura' => '63', 'descripcion' => 'Porcentaje de estudiantes que opina que el plan de estudios incorpora prácticas científicas en la carrera.'],
                ['criterio_id' => $crit('2.1.13'), 'nomenclatura' => '64', 'descripcion' => 'Lista de cursos de métodos de investigación y sus contenidos.'],
                // 2.1.14
                ['criterio_id' => $crit('2.1.14'), 'nomenclatura' => '65', 'descripcion' => 'Descripción de mecanismos de flexibilidad curricular utilizados por la carrera. Indicación de situaciones o disposiciones que dificultan la flexibilidad curricular, y las áreas del conocimiento en que sí se consideran tales mecanismos.'],
                ['criterio_id' => $crit('2.1.14'), 'nomenclatura' => '66', 'descripcion' => 'Porcentaje de estudiantes que opina que la carrera ofrece oportunidades suficientes de flexibilidad curricular para satisfacer sus intereses.'],
                // 2.1.15
                ['criterio_id' => $crit('2.1.15'), 'nomenclatura' => '67', 'descripcion' => 'Lista de actividades extracurriculares que la carrera ofrece a los estudiantes como complementos del plan de estudios.'],
                ['criterio_id' => $crit('2.1.15'), 'nomenclatura' => '68', 'descripcion' => 'Porcentaje de estudiantes que conoce la realización de actividades extracurriculares y participa en ellas.'],
                // 2.1.16
                ['criterio_id' => $crit('2.1.16'), 'nomenclatura' => '69', 'descripcion' => 'Lista de modalidades de trabajos finales de graduación definidas en el plan de estudios y normativa correspondiente.'],
                ['criterio_id' => $crit('2.1.16'), 'nomenclatura' => '70', 'descripcion' => 'Existencia de una normativa que regule las características de las diferentes modalidades de trabajos finales de graduación.'],
                ['criterio_id' => $crit('2.1.16'), 'nomenclatura' => '71', 'descripcion' => 'Distribución del total de graduados de los últimos cuatro años, según modalidades de trabajos finales de graduación.'],
                ['criterio_id' => $crit('2.1.16'), 'nomenclatura' => '72', 'descripcion' => 'Lista de trabajos finales de graduación en las diferentes modalidades.'],
                // 2.1.17
                ['criterio_id' => $crit('2.1.17'), 'nomenclatura' => '73', 'descripcion' => 'Descripción de los procedimientos internos para la verificación de los requisitos de los programas de los cursos y el señalamiento de carencias.'],
                ['criterio_id' => $crit('2.1.17'), 'nomenclatura' => '74', 'descripcion' => 'Disponibilidad de todos los programas de los cursos.'],
                // 2.1.18
                ['criterio_id' => $crit('2.1.18'), 'nomenclatura' => '75', 'descripcion' => 'Disponibilidad de todos los programas de los cursos.'],
                ['criterio_id' => $crit('2.1.18'), 'nomenclatura' => '76', 'descripcion' => 'Descripción de los procedimientos internos para verificar si los objetivos de los cursos están redactados en términos de aprendizajes o competencias.'],
                // 2.1.19
                ['criterio_id' => $crit('2.1.19'), 'nomenclatura' => '77', 'descripcion' => 'Disponibilidad de todos los programas de los cursos.'],
                // 2.2.1
                ['criterio_id' => $crit('2.2.1'), 'nomenclatura' => '78', 'descripcion' => 'Descripción de la normativa específica que regula al personal académico en el ejercicio de la acción docente, y de los mecanismos utilizados para su difusión.'],
                ['criterio_id' => $crit('2.2.1'), 'nomenclatura' => '79', 'descripcion' => 'Porcentaje de personal académico que considera que la normativa que regula sus derechos y deberes se cumple.'],
                // 2.2.2
                ['criterio_id' => $crit('2.2.2'), 'nomenclatura' => '80', 'descripcion' => 'Descripción de las disposiciones que establecen que la jornada de contratación laboral incluye las horas lectivas, de atención de estudiantes fuera de clase, de preparación de lecciones, de elaboración y aplicación de instrumentos para evaluar los aprendizajes, de revisión y valoración de exámenes y otros trabajos, y de coordinación y dirección de trabajos finales de graduación.'],
                // 2.2.3
                ['criterio_id' => $crit('2.2.3'), 'nomenclatura' => '81', 'descripcion' => 'Explicación del modo como la carrera garantiza la participación de su personal académico en actividades de docencia, investigación y extensión social.'],
                ['criterio_id' => $crit('2.2.3'), 'nomenclatura' => '82', 'descripcion' => 'Distribución del tiempo asignado a docencia, investigación y acción social por cada docente.'],
                // 2.2.4
                ['criterio_id' => $crit('2.2.4'), 'nomenclatura' => '83', 'descripcion' => 'Distribución del total del personal académico según el grado académico que ostenta, experiencia docente y profesional, categoría y producción académica, diferenciando entre profesores que imparten cursos propios de la carrera, de servicio y otros, si corresponde.'],
                // 2.2.5
                ['criterio_id' => $crit('2.2.5'), 'nomenclatura' => '84', 'descripcion' => 'Distribución porcentual del personal académico según género, edad y universidades en las que sus integrantes realizaron su formación inicial (bachillerato o licenciatura) y obtuvieron su posgrado.'],
                // 2.2.6
                ['criterio_id' => $crit('2.2.6'), 'nomenclatura' => '85', 'descripcion' => 'Descripción de las políticas, los requisitos y los procedimientos que se aplican para la selección del personal académico.'],
                // 2.2.7
                ['criterio_id' => $crit('2.2.7'), 'nomenclatura' => '86', 'descripcion' => 'Descripción de los mecanismos que se siguen para retener a los mejores académicos.'],
                ['criterio_id' => $crit('2.2.7'), 'nomenclatura' => '87', 'descripcion' => 'Descripción del plan de sustitución del personal académico que deja de laborar para la carrera.'],
                ['criterio_id' => $crit('2.2.7'), 'nomenclatura' => '88', 'descripcion' => 'Descripción de las formas de contratación de personal académico.'],
                ['criterio_id' => $crit('2.2.7'), 'nomenclatura' => '89', 'descripcion' => 'Distribución del personal académico de los últimos cuatro años, según años de servicio en la institución y tipo de contrato.'],
                ['criterio_id' => $crit('2.2.7'), 'nomenclatura' => '90', 'descripcion' => 'De la planilla de profesores de hace cuatro años, indicación del porcentaje de ellos que se mantiene en la actualidad laborando para la carrera.'],
                // 2.2.8
                ['criterio_id' => $crit('2.2.8'), 'nomenclatura' => '91', 'descripcion' => 'Descripción del plan de desarrollo para el personal académico.'],
                ['criterio_id' => $crit('2.2.8'), 'nomenclatura' => '92', 'descripcion' => 'Porcentaje del personal académico que ha participado en procesos de capacitación relacionados con su competencia docente.'],
                ['criterio_id' => $crit('2.2.8'), 'nomenclatura' => '93', 'descripcion' => 'Porcentaje del personal académico que ha participado en actividades de actualización en la disciplina.'],
                ['criterio_id' => $crit('2.2.8'), 'nomenclatura' => '94', 'descripcion' => 'Porcentaje de docentes que realiza estudios para obtener un grado académico superior al actual.'],
                ['criterio_id' => $crit('2.2.8'), 'nomenclatura' => '95', 'descripcion' => 'Descripción de las disposiciones que favorecen la realización de estudios de posgrado.'],
                // 2.2.9
                ['criterio_id' => $crit('2.2.9'), 'nomenclatura' => '96', 'descripcion' => 'Descripción de incentivos o mecanismos de promoción que se apliquen al personal académico.'],
                ['criterio_id' => $crit('2.2.9'), 'nomenclatura' => '97', 'descripcion' => 'Porcentaje de profesores que opina que los incentivos o mecanismos de promoción aplicados propician su desarrollo profesional.'],
                // 2.2.10
                ['criterio_id' => $crit('2.2.10'), 'nomenclatura' => '98',  'descripcion' => 'Descripción del personal académico según jornada de contratación.'],
                ['criterio_id' => $crit('2.2.10'), 'nomenclatura' => '99',  'descripcion' => 'Explicación de la forma como la carrera asegura la interacción entre los profesores y el estudiantado.'],
                // 2.2.11
                ['criterio_id' => $crit('2.2.11'), 'nomenclatura' => '100', 'descripcion' => 'Porcentaje del personal académico que está contratado a tiempo completo.'],
                ['criterio_id' => $crit('2.2.11'), 'nomenclatura' => '101', 'descripcion' => 'Porcentaje del personal académico que opina que su jornada de contratación favorece su participación en la vida académica de la carrera.'],
                ['criterio_id' => $crit('2.2.11'), 'nomenclatura' => '102', 'descripcion' => 'Descripción de la forma como la carrera logra el cumplimiento del criterio.'],
                ['criterio_id' => $crit('2.2.11'), 'nomenclatura' => '103', 'descripcion' => 'Porcentaje del estudiantado que opina que la jornada de contratación del personal académico favorece la participación en la vida académica de la carrera.'],
                ['criterio_id' => $crit('2.2.11'), 'nomenclatura' => '104', 'descripcion' => 'Descripción de la forma como la carrera asegura la interacción entre los profesores y el estudiantado.'],
                // 2.3.1
                ['criterio_id' => $crit('2.3.1'), 'nomenclatura' => '105', 'descripcion' => 'Distribución porcentual del personal administrativo, técnico y de apoyo, según puesto, formación, jornada y aspectos del proceso académico que atienden.'],
                ['criterio_id' => $crit('2.3.1'), 'nomenclatura' => '106', 'descripcion' => 'Porcentajes del personal académico y del estudiantado que opinan que el personal administrativo, el técnico y el de apoyo son suficientes y eficientes.'],
                ['criterio_id' => $crit('2.3.1'), 'nomenclatura' => '107', 'descripcion' => 'Horarios de apertura y cierre de los servicios que brindan el personal administrativo, el técnico y el de apoyo a estudiantes y personal académico.'],
                // 2.3.2
                ['criterio_id' => $crit('2.3.2'), 'nomenclatura' => '108', 'descripcion' => 'Descripción de los procedimientos para la selección y contratación de personal administrativo, técnico y de apoyo.'],
                ['criterio_id' => $crit('2.3.2'), 'nomenclatura' => '109', 'descripcion' => 'Existencia de un manual descriptivo de los cargos y las funciones para el personal administrativo, el técnico y el de apoyo.'],
                // 2.3.3
                ['criterio_id' => $crit('2.3.3'), 'nomenclatura' => '110', 'descripcion' => 'Descripción de las estrategias de evaluación del desempeño que se aplican al personal administrativo, al técnico y al de apoyo, y mecanismos de devolución de resultados.'],
                ['criterio_id' => $crit('2.3.3'), 'nomenclatura' => '111', 'descripcion' => 'Opinión del personal administrativo, del técnico y del de apoyo con respecto a los mecanismos de evaluación y devolución de resultados a los que están sujetos.'],
                ['criterio_id' => $crit('2.3.3'), 'nomenclatura' => '112', 'descripcion' => 'Opinión de estudiantes y personal académico sobre la calidad y calidez de los servicios que reciben del personal administrativo, técnico y de apoyo.'],
                // 2.3.4
                ['criterio_id' => $crit('2.3.4'), 'nomenclatura' => '113', 'descripcion' => 'Descripción del plan de desarrollo profesional para el personal administrativo, el técnico y el de apoyo, acorde con las necesidades del proceso educativo y las demandas institucionales.'],
                // 2.4.1
                ['criterio_id' => $crit('2.4.1'), 'nomenclatura' => '114', 'descripcion' => 'Descripción de la política institucional y de los mecanismos puestos en práctica con el fin de atender la gestión para suplir las necesidades de infraestructura, incluyendo las disposiciones relativas al mantenimiento, reposición y ampliación de la planta física.'],
                ['criterio_id' => $crit('2.4.1'), 'nomenclatura' => '115', 'descripcion' => 'Lista de necesidades (satisfechas e insatisfechas) de mantenimiento, reposición y ampliación de la infraestructura de la carrera en el último año.'],
                ['criterio_id' => $crit('2.4.1'), 'nomenclatura' => '116', 'descripcion' => 'Descripción de las previsiones presupuestarias para atender necesidades de planta física de la carrera.'],
                // 2.4.2
                ['criterio_id' => $crit('2.4.2'), 'nomenclatura' => '117', 'descripcion' => 'Descripción del modo como la infraestructura utilizada por la carrera respeta la normativa para la construcción y habilitación de edificios educativos y lo dispuesto por la Ley de Igualdad de Oportunidades para las Personas con Discapacidad.'],
                ['criterio_id' => $crit('2.4.2'), 'nomenclatura' => '118', 'descripcion' => 'Descripción de la forma en que la infraestructura utilizada por la carrera respeta la normativa establecida en el Reglamento de Construcciones de la Ley N° 4240 del 15 de noviembre de 1968.'],
                // 2.4.3
                ['criterio_id' => $crit('2.4.3'), 'nomenclatura' => '119', 'descripcion' => 'Existencia de un manual con las normas de seguridad, higiene y salud ocupacional que se necesitan, según la naturaleza de la carrera.'],
                ['criterio_id' => $crit('2.4.3'), 'nomenclatura' => '120', 'descripcion' => 'Porcentajes de estudiantes, de personal académico, de personal administrativo y de apoyo que conocen las normas de seguridad, higiene y salud necesarias en la carrera.'],
                // 2.4.4
                ['criterio_id' => $crit('2.4.4'), 'nomenclatura' => '121', 'descripcion' => 'Descripción de las condiciones de seguridad, higiene y salud ocupacional que ofrece la infraestructura, según la naturaleza de la carrera.'],
                ['criterio_id' => $crit('2.4.4'), 'nomenclatura' => '122', 'descripcion' => 'Porcentaje de personal académico, administrativo, técnico, de apoyo y estudiantes que opinan que cuentan con las condiciones de higiene, seguridad y salud ocupacional requeridas para realizar su trabajo.'],
                // 2.4.5
                ['criterio_id' => $crit('2.4.5'), 'nomenclatura' => '123', 'descripcion' => 'Descripción de aulas, auditorios, laboratorios, talleres y otros espacios necesarios, según el total de cursos, grupos, estudiantes matriculados y actividades de la carrera.'],
                ['criterio_id' => $crit('2.4.5'), 'nomenclatura' => '124', 'descripcion' => 'Grado de satisfacción del personal académico y los estudiantes con respecto a la disponibilidad, capacidad y estado de la infraestructura y el mobiliario que utilizan.'],
                // 2.4.6
                ['criterio_id' => $crit('2.4.6'), 'nomenclatura' => '125', 'descripcion' => 'Descripción del espacio físico asignado al personal académico para atención de estudiantes y la realización de actividades propias de la función docente.'],
                ['criterio_id' => $crit('2.4.6'), 'nomenclatura' => '126', 'descripcion' => 'Distribución porcentual de la opinión del personal académico acerca de la suficiencia, idoneidad y oportunidad del espacio físico asignado para sus labores docentes.'],
                // 2.4.7
                ['criterio_id' => $crit('2.4.7'), 'nomenclatura' => '127', 'descripcion' => 'Descripción de los espacios disponibles para la gestión académica y los servicios administrativos y técnicos básicos.'],
                ['criterio_id' => $crit('2.4.7'), 'nomenclatura' => '128', 'descripcion' => 'Distribución porcentual del personal de gestión académica, del administrativo y del técnico, según grado de satisfacción con la disponibilidad y estado de las oficinas y espacios de trabajo.'],
                // 2.4.8
                ['criterio_id' => $crit('2.4.8'), 'nomenclatura' => '129', 'descripcion' => 'Descripción de las zonas bajo techo y al aire libre para reuniones informales de los estudiantes y espacios para estudio.'],
                ['criterio_id' => $crit('2.4.8'), 'nomenclatura' => '130', 'descripcion' => 'Porcentaje de estudiantes que opinan que los espacios para actividades extra-clase son adecuados y están disponibles cuando los necesitan.'],
                // 2.5.1
                ['criterio_id' => $crit('2.5.1'), 'nomenclatura' => '131', 'descripcion' => 'Total de centros de información y recursos disponibles para los estudiantes de la carrera.'],
                ['criterio_id' => $crit('2.5.1'), 'nomenclatura' => '132', 'descripcion' => 'Horarios y total de horas diarias en que están disponibles los servicios del centro de información y recursos para el personal académico y los estudiantes.'],
                ['criterio_id' => $crit('2.5.1'), 'nomenclatura' => '133', 'descripcion' => 'Descripción de los servicios que el centro de información y recursos ofrece.'],
                ['criterio_id' => $crit('2.5.1'), 'nomenclatura' => '134', 'descripcion' => 'Capacidad instalada del centro de información y recursos, en asientos y salas de trabajo individual y grupal.'],
                ['criterio_id' => $crit('2.5.1'), 'nomenclatura' => '135', 'descripcion' => 'Porcentaje del personal académico y de los estudiantes que se muestran satisfechos con diferentes aspectos del centro de información y recursos: horarios, cantidad y calidad de servicios, capacidad instalada (asientos, salas, etc.) y equipo de cómputo.'],
                // 2.5.2
                ['criterio_id' => $crit('2.5.2'), 'nomenclatura' => '136', 'descripcion' => 'Descripción de las políticas que garantizan la actualización permanente de las publicaciones periódicas, de la bibliografía obligatoria y de otros materiales de apoyo para el proceso formativo, y el acceso de los estudiantes a estos libros y materiales.'],
                ['criterio_id' => $crit('2.5.2'), 'nomenclatura' => '137', 'descripcion' => 'Lista de publicaciones periódicas especializadas pertinentes para la carrera, disponibles en el centro de información y recursos.'],
                ['criterio_id' => $crit('2.5.2'), 'nomenclatura' => '138', 'descripcion' => 'Lista de ejemplares de la producción académica del personal académico (incluyendo informes finales de proyectos) disponibles en el centro de información y recursos.'],
                ['criterio_id' => $crit('2.5.2'), 'nomenclatura' => '139', 'descripcion' => 'Descripción de mecanismos que alerten al personal académico y a los estudiantes cuando circule material y libros o documentos recientes.'],
                ['criterio_id' => $crit('2.5.2'), 'nomenclatura' => '140', 'descripcion' => 'Porcentaje de personal académico y estudiantes que opinan que la bibliografía obligatoria está disponible en el centro de información y recursos.'],
                ['criterio_id' => $crit('2.5.2'), 'nomenclatura' => '141', 'descripcion' => 'Distribución porcentual de la opinión de los estudiantes y profesores acerca de su satisfacción con el uso del centro de información y recursos y de la frecuencia con que acceden a éste.'],
                // 2.5.3
                ['criterio_id' => $crit('2.5.3'), 'nomenclatura' => '142', 'descripcion' => 'Lista de redes de información académica (bibliotecas virtuales, bases de datos, revistas electrónicas u otras) disponibles en el área de especialidad de la carrera.'],
                ['criterio_id' => $crit('2.5.3'), 'nomenclatura' => '143', 'descripcion' => 'Porcentaje de personal académico y estudiantes que conoce la disponibilidad de redes de información académica.'],
                ['criterio_id' => $crit('2.5.3'), 'nomenclatura' => '144', 'descripcion' => 'Descripción de las facilidades que se ofrecen para que el personal académico y los estudiantes utilicen las redes de información académica disponibles.'],
                ['criterio_id' => $crit('2.5.3'), 'nomenclatura' => '145', 'descripcion' => 'Descripción de las actividades desarrolladas para sensibilizar y entrenar a docentes y estudiantes en el uso de medios de información.'],
                // 2.5.4
                ['criterio_id' => $crit('2.5.4'), 'nomenclatura' => '146', 'descripcion' => 'Número y distribución del personal responsable de brindar los servicios en el centro de información y recursos según grado académico, área de especialidad y jornada laboral.'],
                // 2.5.5
                ['criterio_id' => $crit('2.5.5'), 'nomenclatura' => '147', 'descripcion' => 'Descripción del mecanismo que se sigue para la compra de material bibliográfico para la carrera.'],
                ['criterio_id' => $crit('2.5.5'), 'nomenclatura' => '148', 'descripcion' => 'Presupuesto anual disponible y ejecutado para la adquisición de materiales bibliográficos requeridos por la carrera en los últimos cinco años.'],
                // 2.6.1
                ['criterio_id' => $crit('2.6.1'), 'nomenclatura' => '149', 'descripcion' => 'Porcentaje de personal académico, administrativo y técnico que se muestra satisfecho con el estado del equipo de cómputo y multimedia, y con su acceso a él.'],
                // 2.6.2
                ['criterio_id' => $crit('2.6.2'), 'nomenclatura' => '150', 'descripcion' => 'Porcentaje de estudiantes que tiene acceso pleno a laboratorios de informática.'],
                ['criterio_id' => $crit('2.6.2'), 'nomenclatura' => '151', 'descripcion' => 'Disponibilidad de equipos de cómputo según total de estudiantes.'],
                ['criterio_id' => $crit('2.6.2'), 'nomenclatura' => '152', 'descripcion' => 'Distribución del equipo de cómputo disponible según condiciones de actualización, disponibilidad de software y recursos periféricos.'],
                ['criterio_id' => $crit('2.6.2'), 'nomenclatura' => '153', 'descripcion' => 'Porcentaje de estudiantes que opinan que la disponibilidad y la calidad del equipo de cómputo en los laboratorios es la requerida.'],
                // 2.6.3
                ['criterio_id' => $crit('2.6.3'), 'nomenclatura' => '154', 'descripcion' => 'Número de equipos de multimedia disponibles para usar en las aulas por el personal académico y los estudiantes de la carrera.'],
                ['criterio_id' => $crit('2.6.3'), 'nomenclatura' => '155', 'descripcion' => 'Distribución porcentual de la opinión del personal académico y los estudiantes sobre disponibilidad del equipo multimedia en las aulas cuando lo requieren, condiciones en que se encuentra y frecuencia con que lo usan.'],
                // 2.6.4
                ['criterio_id' => $crit('2.6.4'), 'nomenclatura' => '156', 'descripcion' => 'Descripción del equipo especializado disponible en laboratorios o talleres según cantidad y condiciones.'],
                ['criterio_id' => $crit('2.6.4'), 'nomenclatura' => '157', 'descripcion' => 'Porcentaje de personal académico y estudiantes que considera que el equipo especializado con que cuentan los laboratorios y talleres es suficiente, está actualizado, disponible y en buen estado.'],
                // 2.6.5
                ['criterio_id' => $crit('2.6.5'), 'nomenclatura' => '158', 'descripcion' => 'Porcentaje del personal administrativo, del académico, del técnico, del de apoyo y de los estudiantes que opinan que cuentan con los recursos materiales necesarios para el proceso formativo y para todas las labores que lo acompañan.'],
                // 2.7.1
                ['criterio_id' => $crit('2.7.1'), 'nomenclatura' => '159', 'descripcion' => 'Presupuesto total y tasa de crecimiento del presupuesto anual de la carrera, correspondiente a los últimos cuatro años y en colones constantes, distribuido según gastos de operación, de inversión y de servicios personales.'],
                ['criterio_id' => $crit('2.7.1'), 'nomenclatura' => '160', 'descripcion' => 'Descripción sobre la suficiencia del presupuesto asignado a la carrera para cumplir con los objetivos de ésta y lograr el mejoramiento continuo.'],
                // 2.7.2
                ['criterio_id' => $crit('2.7.2'), 'nomenclatura' => '161', 'descripcion' => 'Existencia de políticas y normativa sobre la captación de recursos externos.'],
                ['criterio_id' => $crit('2.7.2'), 'nomenclatura' => '162', 'descripcion' => 'Destino de los recursos que recibe la carrera de fuentes externas.'],

                // ── DIMENSIÓN 3 ───────────────────────────────────────────────
                // 3.1.1
                ['criterio_id' => $crit('3.1.1'), 'nomenclatura' => '163', 'descripcion' => 'Descripción de la normativa y del mecanismo seguido para que el personal académico y la dirección participen en las modificaciones realizadas al plan de estudios.'],
                ['criterio_id' => $crit('3.1.1'), 'nomenclatura' => '164', 'descripcion' => 'Existencia de acuerdos o minutas respecto a las modificaciones hechas al plan de estudios.'],
                // 3.1.2
                ['criterio_id' => $crit('3.1.2'), 'nomenclatura' => '165', 'descripcion' => 'Descripción, lista y frecuencia con que se llevan a cabo actividades en las que la carrera solicita la participación del personal académico.'],
                ['criterio_id' => $crit('3.1.2'), 'nomenclatura' => '166', 'descripcion' => 'Disponibilidad de minutas de las reuniones de coordinación.'],
                ['criterio_id' => $crit('3.1.2'), 'nomenclatura' => '167', 'descripcion' => 'Existencia de mecanismos para el control y seguimiento de los acuerdos.'],
                ['criterio_id' => $crit('3.1.2'), 'nomenclatura' => '168', 'descripcion' => 'Porcentaje del personal académico que considera que los mecanismos para su convocatoria y participación en las reuniones de coordinación son adecuados.'],
                // 3.1.3
                ['criterio_id' => $crit('3.1.3'), 'nomenclatura' => '169', 'descripcion' => 'Descripción de los mecanismos administrativos que permiten verificar el cumplimiento de las responsabilidades del personal académico.'],
                // 3.1.4
                ['criterio_id' => $crit('3.1.4'), 'nomenclatura' => '170', 'descripcion' => 'Descripción de los mecanismos y designación de los responsables de evaluar el desempeño del personal académico.'],
                ['criterio_id' => $crit('3.1.4'), 'nomenclatura' => '171', 'descripcion' => 'Descripción de las políticas y mecanismos utilizados para superar las deficiencias detectadas en la evaluación del desempeño del personal académico.'],
                ['criterio_id' => $crit('3.1.4'), 'nomenclatura' => '172', 'descripcion' => 'Instrumento utilizado para evaluar al personal académico.'],
                ['criterio_id' => $crit('3.1.4'), 'nomenclatura' => '173', 'descripcion' => 'Distribución porcentual del personal académico según resultados obtenidos en los diferentes aspectos de la evaluación en los últimos cuatro años.'],
                ['criterio_id' => $crit('3.1.4'), 'nomenclatura' => '174', 'descripcion' => 'Descripción de la normativa con las responsabilidades del personal académico.'],
                // 3.1.5
                ['criterio_id' => $crit('3.1.5'), 'nomenclatura' => '175', 'descripcion' => 'Descripción de las estrategias y el programa o proyecto relacionado con la innovación y actualización de los métodos de enseñanza, a los que tiene acceso la carrera.'],
                ['criterio_id' => $crit('3.1.5'), 'nomenclatura' => '176', 'descripcion' => 'Referencia a los principales resultados que dichas estrategias, programas o proyectos han aportado para el mejoramiento de la carrera.'],
                ['criterio_id' => $crit('3.1.5'), 'nomenclatura' => '177', 'descripcion' => 'Facilidades que brinda la carrera para que el personal académico acceda a investigación reciente sobre el campo de la didáctica y la utilice.'],
                ['criterio_id' => $crit('3.1.5'), 'nomenclatura' => '178', 'descripcion' => 'Opinión del personal académico sobre el grado en que las estrategias establecidas por la carrera le permiten estar actualizado en didáctica universitaria.'],
                // 3.2.1
                ['criterio_id' => $crit('3.2.1'), 'nomenclatura' => '179', 'descripcion' => 'Descripción de la congruencia entre los objetivos del plan de estudios, los cursos y el tipo de actividades de aprendizaje puestos en práctica.'],
                // 3.2.2
                ['criterio_id' => $crit('3.2.2'), 'nomenclatura' => '180', 'descripcion' => 'Descripción de los métodos de enseñanza utilizados por la carrera.'],
                // 3.2.3
                ['criterio_id' => $crit('3.2.3'), 'nomenclatura' => '181', 'descripcion' => 'Descripción de los mecanismos y estrategias que utiliza la carrera para promover en los estudiantes los aprendizajes cognitivos, el desarrollo de destrezas, la formación de actitudes positivas, el interés por el aprendizaje continuo, el pensamiento crítico, la creatividad y el pensamiento autónomo.'],
                // 3.2.4
                ['criterio_id' => $crit('3.2.4'), 'nomenclatura' => '182', 'descripcion' => 'Descripción de las facilidades que ofrece la carrera para que los estudiantes y profesores participen en actividades fuera de las instalaciones de la universidad, cuando el plan de estudios así lo requiera.'],
                ['criterio_id' => $crit('3.2.4'), 'nomenclatura' => '183', 'descripcion' => 'Distribución porcentual de la opinión del personal académico y de los estudiantes sobre las facilidades que brinda la carrera para realizar giras o actividades fuera de las instalaciones universitarias.'],
                // 3.2.5
                ['criterio_id' => $crit('3.2.5'), 'nomenclatura' => '184', 'descripcion' => 'Descripción de los mecanismos utilizados por la carrera para garantizar que los métodos de evaluación aplicados a los estudiantes permiten evaluar no solo la adquisición de conocimientos, sino también de habilidades y destrezas.'],
                // 3.2.6
                ['criterio_id' => $crit('3.2.6'), 'nomenclatura' => '185', 'descripcion' => 'Porcentaje de estudiantes que afirman que durante las dos primeras semanas de clase se les informó sobre la propuesta de evaluación de cada curso matriculado y se les explicaron todos los detalles de esa propuesta.'],
                // 3.2.7
                ['criterio_id' => $crit('3.2.7'), 'nomenclatura' => '186', 'descripcion' => 'Opinión de los estudiantes sobre la concordancia entre la metodología de enseñanza y aprendizaje y los métodos de evaluación de los aprendizajes.'],
                // 3.3.1
                ['criterio_id' => $crit('3.3.1'), 'nomenclatura' => '187', 'descripcion' => 'Existencia de un documento oficial que describa la estructura organizativa y el funcionamiento de la universidad, de la unidad académica y de la carrera.'],
                ['criterio_id' => $crit('3.3.1'), 'nomenclatura' => '188', 'descripcion' => 'Descripción de la ubicación de la carrera dentro de la organización universitaria.'],
                ['criterio_id' => $crit('3.3.1'), 'nomenclatura' => '189', 'descripcion' => 'Descripción de la estructura administrativa con que se cuenta para la gestión de la carrera.'],
                // 3.3.2
                ['criterio_id' => $crit('3.3.2'), 'nomenclatura' => '190', 'descripcion' => 'Documento disponible con un plan estratégico que guíe el funcionamiento y el desarrollo de la carrera, con un horizonte de vigencia de cinco años al menos.'],
                ['criterio_id' => $crit('3.3.2'), 'nomenclatura' => '191', 'descripcion' => 'Lista de los principales resultados visibles del plan estratégico a la fecha.'],
                // 3.3.3
                ['criterio_id' => $crit('3.3.3'), 'nomenclatura' => '192', 'descripcion' => 'Descripción del mecanismo que se sigue para mantener un registro permanente y actualizado de información sobre el personal académico.'],
                ['criterio_id' => $crit('3.3.3'), 'nomenclatura' => '193', 'descripcion' => 'Estadísticas anuales disponibles sobre información del personal académico (carga académica, cursos y grupos, producción académica, entre otros).'],
                ['criterio_id' => $crit('3.3.3'), 'nomenclatura' => '194', 'descripcion' => 'Descripción de la forma como se utilizan los datos sobre el personal académico para la toma de decisiones.'],
                // 3.3.4
                ['criterio_id' => $crit('3.3.4'), 'nomenclatura' => '195', 'descripcion' => 'Porcentaje del personal académico y administrativo que reporta un clima de trabajo que favorece el logro de los objetivos educativos de la carrera.'],
                // 3.3.5
                ['criterio_id' => $crit('3.3.5'), 'nomenclatura' => '196', 'descripcion' => 'Normativa con los requisitos establecidos para la persona que ocupe la dirección de la carrera.'],
                ['criterio_id' => $crit('3.3.5'), 'nomenclatura' => '197', 'descripcion' => 'Normativa con el procedimiento que se sigue para nombrar a la persona que ocupe la dirección de la carrera.'],
                ['criterio_id' => $crit('3.3.5'), 'nomenclatura' => '198', 'descripcion' => 'Jornada laboral que dedica la persona nombrada a las labores de dirección de la carrera, en forma exclusiva.'],
                ['criterio_id' => $crit('3.3.5'), 'nomenclatura' => '199', 'descripcion' => 'Periodo de nombramiento y tiempo de permanencia en el puesto.'],
                // 3.3.6
                ['criterio_id' => $crit('3.3.6'), 'nomenclatura' => '200', 'descripcion' => 'Descripción del perfil de la persona que ocupa la dirección de la carrera.'],
                ['criterio_id' => $crit('3.3.6'), 'nomenclatura' => '201', 'descripcion' => 'Currículum vítae de la persona que ocupa la dirección.'],
                ['criterio_id' => $crit('3.3.6'), 'nomenclatura' => '202', 'descripcion' => 'Opinión del personal académico y de los estudiantes de la carrera sobre el liderazgo del director o directora en el ejercicio de sus funciones.'],
                // 3.3.7
                ['criterio_id' => $crit('3.3.7'), 'nomenclatura' => '203', 'descripcion' => 'Referencia a los responsables de la orientación y gestión de la carrera.'],
                ['criterio_id' => $crit('3.3.7'), 'nomenclatura' => '204', 'descripcion' => 'Descripción de las actividades que desarrolla ese núcleo académico.'],
                // 3.3.8
                ['criterio_id' => $crit('3.3.8'), 'nomenclatura' => '205', 'descripcion' => 'Descripción de los mecanismos que utiliza la dirección de la carrera para ejercer el control de la ejecución del plan de estudios.'],
                ['criterio_id' => $crit('3.3.8'), 'nomenclatura' => '206', 'descripcion' => 'Opinión del personal académico sobre la efectividad de los mecanismos con que la dirección de la carrera ejerce control para la ejecución del plan de estudios.'],
                // 3.3.9
                ['criterio_id' => $crit('3.3.9'), 'nomenclatura' => '207', 'descripcion' => 'Porcentaje de personal académico y de estudiantes que reportan estar enterados de los cambios en el plan de estudios, antes de su puesta en vigencia.'],
                // 3.3.10
                ['criterio_id' => $crit('3.3.10'), 'nomenclatura' => '208', 'descripcion' => 'Lista de las instancias académicas vinculadas con la ejecución del plan de estudios.'],
                ['criterio_id' => $crit('3.3.10'), 'nomenclatura' => '209', 'descripcion' => 'Descripción de los mecanismos establecidos para coordinar con otras instancias académicas.'],
                // 3.3.11
                ['criterio_id' => $crit('3.3.11'), 'nomenclatura' => '210', 'descripcion' => 'Descripción de los mecanismos que se ponen en práctica para la evaluación, revisión, reflexión y actualización del plan de estudio.'],
                ['criterio_id' => $crit('3.3.11'), 'nomenclatura' => '211', 'descripcion' => 'Opinión del personal académico y de los estudiantes acerca de la existencia y calidad de los mecanismos para la evaluación, revisión, reflexión y actualización periódica del plan de estudios.'],
                // 3.3.12
                ['criterio_id' => $crit('3.3.12'), 'nomenclatura' => '212', 'descripcion' => 'Total de ocasiones en que el plan de estudios ha sido modificado en los últimos cuatro años.'],
                ['criterio_id' => $crit('3.3.12'), 'nomenclatura' => '213', 'descripcion' => 'Actas, resoluciones u otros documentos que recojan las modificaciones introducidas al plan de estudios en los últimos cuatro años.'],
                // 3.3.13
                ['criterio_id' => $crit('3.3.13'), 'nomenclatura' => '214', 'descripcion' => 'Descripción de los mecanismos para promover espacios de reunión entre el personal académico y la dirección de la carrera, y frecuencia de éstos.'],
                ['criterio_id' => $crit('3.3.13'), 'nomenclatura' => '215', 'descripcion' => 'Opinión del personal académico respecto a la existencia y frecuencia de espacios de reunión que posibiliten la información, la coordinación, el diálogo y la opinión sobre aspectos académicos y administrativos de la carrera.'],
                // 3.3.14
                ['criterio_id' => $crit('3.3.14'), 'nomenclatura' => '216', 'descripcion' => 'Descripción de las actividades formales realizadas con el personal académico para conocer, analizar y evaluar aspectos de la carrera y tomar decisiones sobre estos, e indicación de los logros derivados de tales actividades.'],
                // 3.3.15
                ['criterio_id' => $crit('3.3.15'), 'nomenclatura' => '217', 'descripcion' => 'Descripción de los mecanismos formales que pone en práctica la carrera para que el personal académico de un mismo curso, del mismo nivel o eje curricular coordine e integre sus acciones, y para darles seguimiento a éstas.'],
                ['criterio_id' => $crit('3.3.15'), 'nomenclatura' => '218', 'descripcion' => 'Opinión del personal académico que ofrece un mismo curso o cursos de un mismo nivel o eje curricular, acerca de los mecanismos que se utilizan para su integración y para el seguimiento por parte de los encargados de la carrera.'],
                // 3.3.16
                ['criterio_id' => $crit('3.3.16'), 'nomenclatura' => '219', 'descripcion' => 'Descripción de los mecanismos seguidos en los últimos cuatro años para ajustar a la conveniencia de los estudiantes la oferta de cursos y sus horarios.'],
                // 3.3.17
                ['criterio_id' => $crit('3.3.17'), 'nomenclatura' => '220', 'descripcion' => 'Frecuencia con la que se ofreció cada curso en los últimos cuatro años.'],
                ['criterio_id' => $crit('3.3.17'), 'nomenclatura' => '221', 'descripcion' => 'Descripción de las opciones diferentes a la matrícula ordinaria que se ofrecen a los estudiantes para llevar cursos; por ejemplo, por suficiencia, mediante tutoría, en línea, y otras.'],
                ['criterio_id' => $crit('3.3.17'), 'nomenclatura' => '222', 'descripcion' => 'Distribución porcentual de la opinión de los estudiantes acerca del acceso a los cursos de la carrera y la frecuencia con que se imparten.'],
                // 3.3.18
                ['criterio_id' => $crit('3.3.18'), 'nomenclatura' => '223', 'descripcion' => 'Descripción de políticas y procedimientos para el reemplazo de personal.'],
                // 3.3.19
                ['criterio_id' => $crit('3.3.19'), 'nomenclatura' => '224', 'descripcion' => 'Existencia de un programa de inducción para el personal nuevo, tanto el académico como el administrativo.'],
                // 3.3.20
                ['criterio_id' => $crit('3.3.20'), 'nomenclatura' => '225', 'descripcion' => 'Descripción del plan de mejoramiento de la carrera con lista de disposiciones, decisiones, acciones, responsables, plazos y recursos, que reflejen compromiso con el mejoramiento continuo.'],
                // 3.4.1
                ['criterio_id' => $crit('3.4.1'), 'nomenclatura' => '226', 'descripcion' => 'Existencia de políticas o disposiciones que guíen los diferentes aspectos que se relacionan con la actividad investigativa.'],
                ['criterio_id' => $crit('3.4.1'), 'nomenclatura' => '227', 'descripcion' => 'Descripción de políticas y mecanismos que se siguen para la aprobación de las investigaciones que se realizan.'],
                ['criterio_id' => $crit('3.4.1'), 'nomenclatura' => '228', 'descripcion' => 'Opinión del personal académico acerca de las oportunidades que tiene para realizar investigación.'],
                // 3.4.2
                ['criterio_id' => $crit('3.4.2'), 'nomenclatura' => '229', 'descripcion' => 'Facilidades que brinda la carrera para que el personal académico utilice investigación reciente sobre el campo de su especialidad y acceda a ella oportunamente.'],
                ['criterio_id' => $crit('3.4.2'), 'nomenclatura' => '230', 'descripcion' => 'Opinión del personal académico acerca de las facilidades que ofrece la carrera para su actualización en el campo de su especialidad.'],
                // 3.4.3
                ['criterio_id' => $crit('3.4.3'), 'nomenclatura' => '231', 'descripcion' => 'Descripción de los mecanismos mediante los cuales se logra la articulación de los proyectos de investigación con los objetivos de la carrera.'],
                ['criterio_id' => $crit('3.4.3'), 'nomenclatura' => '232', 'descripcion' => 'Descripción de la forma en que se aprovechan los resultados de las investigaciones.'],
                // 3.4.4
                ['criterio_id' => $crit('3.4.4'), 'nomenclatura' => '233', 'descripcion' => 'Porcentaje del personal académico que realiza actividades de investigación y que opina que los recursos disponibles para su labor son suficientes.'],
                ['criterio_id' => $crit('3.4.4'), 'nomenclatura' => '234', 'descripcion' => 'Total de proyectos o acciones de investigación vigentes.'],
                ['criterio_id' => $crit('3.4.4'), 'nomenclatura' => '235', 'descripcion' => 'Cantidad de recursos destinados a proyectos de investigación por cada fuente de financiamiento.'],
                // 3.4.5
                ['criterio_id' => $crit('3.4.5'), 'nomenclatura' => '236', 'descripcion' => 'Descripción de los mecanismos utilizados para lograr que el personal académico a cargo de materias propias de la carrera desarrolle actividades de pensamiento científico riguroso; por ejemplo, asignación de carga académica suficiente, de equipo apropiado, y nombramiento de personal de apoyo.'],
                ['criterio_id' => $crit('3.4.5'), 'nomenclatura' => '237', 'descripcion' => 'Porcentaje del personal académico que ofrece asignaturas de la carrera que está participando o ha participado en actividades de pensamiento científico riguroso.'],
                ['criterio_id' => $crit('3.4.5'), 'nomenclatura' => '238', 'descripcion' => 'Porcentaje del personal académico que imparte materias de la carrera que opina que los incentivos dados por la carrera para desarrollar actividades de pensamiento científico riguroso son suficientes y oportunos.'],
                // 3.4.6
                ['criterio_id' => $crit('3.4.6'), 'nomenclatura' => '239', 'descripcion' => 'Lista de centros, grupos, redes o programas de investigación con los cuales la carrera mantiene relaciones.'],
                ['criterio_id' => $crit('3.4.6'), 'nomenclatura' => '240', 'descripcion' => 'Descripción de la naturaleza de las relaciones de la carrera con esos centros, redes o programas.'],
                // 3.4.7
                ['criterio_id' => $crit('3.4.7'), 'nomenclatura' => '241', 'descripcion' => 'Nombre y descripción de proyectos o acciones de investigación realizados en los últimos cuatro años.'],
                ['criterio_id' => $crit('3.4.7'), 'nomenclatura' => '242', 'descripcion' => 'Descripción de los aportes innovadores.'],
                // 3.4.8
                ['criterio_id' => $crit('3.4.8'), 'nomenclatura' => '243', 'descripcion' => 'Mecanismos que se ponen en práctica para estimular y difundir los resultados de las investigaciones del personal en su práctica docente.'],
                ['criterio_id' => $crit('3.4.8'), 'nomenclatura' => '244', 'descripcion' => 'Descripción de la forma en que el personal académico y los estudiantes participan en la realización o utilización de investigaciones recientes.'],
                // 3.4.9
                ['criterio_id' => $crit('3.4.9'), 'nomenclatura' => '245', 'descripcion' => 'Muestras de las publicaciones en las que aparecen los resultados de las investigaciones.'],
                ['criterio_id' => $crit('3.4.9'), 'nomenclatura' => '246', 'descripcion' => 'Lista de investigaciones difundidas por otros medios como seminarios, congresos, libros y foros, entre otros.'],
                // 3.5.1
                ['criterio_id' => $crit('3.5.1'), 'nomenclatura' => '247', 'descripcion' => 'Existencia de políticas y procedimientos que favorezcan la participación del personal académico y estudiantes en actividades de extensión.'],
                // 3.5.2
                ['criterio_id' => $crit('3.5.2'), 'nomenclatura' => '248', 'descripcion' => 'Descripción de los lineamientos que guían la realización de proyectos de extensión.'],
                // 3.5.3
                ['criterio_id' => $crit('3.5.3'), 'nomenclatura' => '249', 'descripcion' => 'Descripción de las acciones de extensión mediante las cuales la carrera se ha proyectado sobre su entorno en el último año.'],
                ['criterio_id' => $crit('3.5.3'), 'nomenclatura' => '250', 'descripcion' => 'Listado de acciones de extensión ejecutadas en los últimos cuatro años.'],
                // 3.5.4
                ['criterio_id' => $crit('3.5.4'), 'nomenclatura' => '251', 'descripcion' => 'Descripción de las estrategias de promoción y de los incentivos ofrecidos al personal docente para garantizar la expansión de los servicios universitarios.'],
                // 3.5.5
                ['criterio_id' => $crit('3.5.5'), 'nomenclatura' => '252', 'descripcion' => 'Porcentaje de personal académico que realiza actividades de extensión, cuya opinión indica que los recursos con que cuenta para esa labor son suficientes.'],
                ['criterio_id' => $crit('3.5.5'), 'nomenclatura' => '253', 'descripcion' => 'Cantidad de recursos destinados a proyectos de extensión por fuente de financiamiento.'],
                // 3.5.6
                ['criterio_id' => $crit('3.5.6'), 'nomenclatura' => '254', 'descripcion' => 'Descripción de las modalidades de participación de agentes sociales involucrados en las acciones de extensión.'],
                // 3.5.7
                ['criterio_id' => $crit('3.5.7'), 'nomenclatura' => '255', 'descripcion' => 'Estadísticas o datos cuantitativos y cualitativos de las acciones de extensión ejecutadas por la carrera en el último año.'],
                ['criterio_id' => $crit('3.5.7'), 'nomenclatura' => '256', 'descripcion' => 'Descripción de cómo la carrera utiliza los datos de sus actividades de extensión para planificar sus futuras acciones en esa área.'],
                // 3.5.8
                ['criterio_id' => $crit('3.5.8'), 'nomenclatura' => '257', 'descripcion' => 'Descripción de los mecanismos utilizados por la carrera para la difusión de sus acciones de extensión.'],
                ['criterio_id' => $crit('3.5.8'), 'nomenclatura' => '258', 'descripcion' => 'Muestras de productos comunicacionales utilizados para difundir las acciones de extensión.'],
                ['criterio_id' => $crit('3.5.8'), 'nomenclatura' => '259', 'descripcion' => 'Descripción de los mecanismos utilizados por la carrera para dar seguimiento a las acciones de extensión, de tal forma que se evidencie la apropiación de conocimientos por parte de los agentes sociales involucrados.'],
                // 3.5.9
                ['criterio_id' => $crit('3.5.9'), 'nomenclatura' => '260', 'descripcion' => 'Descripción de los mecanismos que la carrera utiliza para evaluar los resultados de sus acciones de extensión.'],
                ['criterio_id' => $crit('3.5.9'), 'nomenclatura' => '261', 'descripcion' => 'Descripción de la forma en que se evaluaron los resultados de las acciones de extensión ejecutadas por la carrera en el último año.'],
                // 3.6.1
                ['criterio_id' => $crit('3.6.1'), 'nomenclatura' => '262', 'descripcion' => 'Existencia de políticas para la atención integral del estudiantado.'],
                ['criterio_id' => $crit('3.6.1'), 'nomenclatura' => '263', 'descripcion' => 'Lista de servicios disponibles para atender integralmente a los estudiantes.'],
                // 3.6.2
                ['criterio_id' => $crit('3.6.2'), 'nomenclatura' => '264', 'descripcion' => 'Normativa y procedimientos existentes para cumplir las leyes relativas a discapacidad, hostigamiento sexual y otras.'],
                ['criterio_id' => $crit('3.6.2'), 'nomenclatura' => '265', 'descripcion' => 'Opinión del personal académico, administrativo y estudiantes acerca del cumplimiento de esas disposiciones legales.'],
                // 3.6.3 (en el PDF el criterio 3.6.3 existe, el JSON lo omitió)
                ['criterio_id' => $crit('3.6.3'), 'nomenclatura' => '266', 'descripcion' => 'Existencia de normativa para nombrar la asociación de estudiantes, y descripción de las facilidades ofrecidas por la carrera a esa asociación.'],
                ['criterio_id' => $crit('3.6.3'), 'nomenclatura' => '267', 'descripcion' => 'Opinión de los miembros de la asociación sobre las facilidades que ofrece la carrera para el funcionamiento adecuado de esa organización.'],
                // 3.6.4
                ['criterio_id' => $crit('3.6.4'), 'nomenclatura' => '268', 'descripcion' => 'Existencia de reglamento o normativa estudiantil y aspectos que regula esa normativa.'],
                ['criterio_id' => $crit('3.6.4'), 'nomenclatura' => '269', 'descripcion' => 'Mecanismos de divulgación de la normativa y disponibilidad para que los estudiantes la consulten.'],
                ['criterio_id' => $crit('3.6.4'), 'nomenclatura' => '270', 'descripcion' => 'Instancias que regulan el cumplimiento de la normativa estudiantil.'],
                // 3.6.5
                ['criterio_id' => $crit('3.6.5'), 'nomenclatura' => '271', 'descripcion' => 'Instancia responsable de ofrecer servicios de orientación vocacional y ocupacional a los estudiantes, y descripción de los servicios que ofrece.'],
                // 3.6.6
                ['criterio_id' => $crit('3.6.6'), 'nomenclatura' => '272', 'descripcion' => 'Descripción de las acciones, facilidades y asesoría que se brindan a los estudiantes con alguna discapacidad.'],
                ['criterio_id' => $crit('3.6.6'), 'nomenclatura' => '273', 'descripcion' => 'Opinión de estudiantes con alguna discapacidad sobre el acceso que tienen a los diferentes servicios y sobre su participación en las actividades que implica el proceso educativo.'],
                // 3.6.7
                ['criterio_id' => $crit('3.6.7'), 'nomenclatura' => '274', 'descripcion' => 'Descripción de la instancia responsable del registro, control y certificación de los datos académicos estudiantiles.'],
                ['criterio_id' => $crit('3.6.7'), 'nomenclatura' => '275', 'descripcion' => 'Descripción de los mecanismos de seguridad, permisos y verificación que se siguen para garantizar la veracidad de la información y proteger su confidencialidad.'],
                // 3.6.8
                ['criterio_id' => $crit('3.6.8'), 'nomenclatura' => '276', 'descripcion' => 'Descripción del servicio que favorece el acceso de los estudiantes a becas, ayudas económicas o fuentes de financiamiento de los estudios.'],
                ['criterio_id' => $crit('3.6.8'), 'nomenclatura' => '277', 'descripcion' => 'Porcentaje de estudiantes de la carrera becados, con ayudas económicas o financiamiento, que opinan que estos apoyos recibidos son suficientes.'],
                // 3.6.9
                ['criterio_id' => $crit('3.6.9'), 'nomenclatura' => '278', 'descripcion' => 'Disponibilidad de estadísticas sobre el uso de los servicios académicos y estudiantiles.'],
                ['criterio_id' => $crit('3.6.9'), 'nomenclatura' => '279', 'descripcion' => 'Formas en que dicha información se utiliza para la toma de decisiones y el mejoramiento de los servicios.'],
                ['criterio_id' => $crit('3.6.9'), 'nomenclatura' => '280', 'descripcion' => 'Opinión de los estudiantes sobre la calidad de los servicios académicos y estudiantiles.'],
                // 3.6.10
                ['criterio_id' => $crit('3.6.10'), 'nomenclatura' => '281', 'descripcion' => 'Horario en que atienden las dependencias administrativas y las que dan servicios al estudiante.'],
                ['criterio_id' => $crit('3.6.10'), 'nomenclatura' => '282', 'descripcion' => 'Porcentaje de estudiantes que se muestran satisfechos con los servicios que se les ofrecen y con el horario que éstos tienen.'],
                // 3.6.11
                ['criterio_id' => $crit('3.6.11'), 'nomenclatura' => '283', 'descripcion' => 'Descripción de los mecanismos utilizados para ofrecer al estudiantado guía sobre los procesos administrativos.'],
                ['criterio_id' => $crit('3.6.11'), 'nomenclatura' => '284', 'descripcion' => 'Porcentaje de estudiantes que informa haber recibido guía para realizar procesos administrativos fundamentales y que da opinión sobre ésta.'],
                // 3.6.12
                ['criterio_id' => $crit('3.6.12'), 'nomenclatura' => '285', 'descripcion' => 'Medios vigentes para que el estudiantado exprese sus opiniones y el nivel de satisfacción con su carrera y otros aspectos.'],
                ['criterio_id' => $crit('3.6.12'), 'nomenclatura' => '286', 'descripcion' => 'Nivel de satisfacción del estudiantado con la carrera, el personal académico, los servicios y las actividades de la institución.'],
                ['criterio_id' => $crit('3.6.12'), 'nomenclatura' => '287', 'descripcion' => 'Porcentaje de estudiantes que indica que pueden expresar sus opiniones sin temor a represalias.'],
                // 3.6.13
                ['criterio_id' => $crit('3.6.13'), 'nomenclatura' => '288', 'descripcion' => 'Descripción de las instancias de la carrera a las que tienen acceso los representantes estudiantiles.'],
                ['criterio_id' => $crit('3.6.13'), 'nomenclatura' => '289', 'descripcion' => 'Mecanismos que se siguen para que los estudiantes eleven inquietudes sobre asuntos de su interés.'],
                ['criterio_id' => $crit('3.6.13'), 'nomenclatura' => '290', 'descripcion' => 'Opinión de los representantes de los estudiantes sobre las oportunidades de participación en las instancias de la carrera donde se deciden asuntos de interés estudiantil y el grado en que sus opiniones son tomadas en cuenta.'],
                // 3.6.14
                ['criterio_id' => $crit('3.6.14'), 'nomenclatura' => '291', 'descripcion' => 'Descripción de disposiciones sobre la asesoría académica curricular para el estudiantado.'],
                ['criterio_id' => $crit('3.6.14'), 'nomenclatura' => '292', 'descripcion' => 'Total de años en que se ha ofrecido la asesoría académica curricular.'],
                ['criterio_id' => $crit('3.6.14'), 'nomenclatura' => '293', 'descripcion' => 'Porcentaje de estudiantes que se muestran satisfechos con el servicio de asesoría académica curricular.'],
                // 3.6.15
                ['criterio_id' => $crit('3.6.15'), 'nomenclatura' => '294', 'descripcion' => 'Descripción de la normativa y su aplicación para que el personal docente ofrezca guía y tutoría académica a los estudiantes.'],
                ['criterio_id' => $crit('3.6.15'), 'nomenclatura' => '295', 'descripcion' => 'Número de horas semanales disponibles por curso para ofrecer tutoría y guía académica a los estudiantes.'],
                ['criterio_id' => $crit('3.6.15'), 'nomenclatura' => '296', 'descripcion' => 'Opinión de los estudiantes acerca de la existencia, suficiencia y calidad de la tutoría y guía académica que reciben.'],
                // 3.6.16
                ['criterio_id' => $crit('3.6.16'), 'nomenclatura' => '297', 'descripcion' => 'Descripción de las instancias existentes para brindar seguimiento al desempeño del estudiante y remitir los problemas detectados a los servicios de apoyo existentes.'],
                // 3.6.17
                ['criterio_id' => $crit('3.6.17'), 'nomenclatura' => '298', 'descripcion' => 'Descripción de las acciones de inducción que se desarrollan para los estudiantes nuevos.'],
                ['criterio_id' => $crit('3.6.17'), 'nomenclatura' => '299', 'descripcion' => 'Porcentaje de estudiantes de primer ingreso que opina que las acciones de inducción que se realizan son útiles.'],

                // ── DIMENSIÓN 4 ───────────────────────────────────────────────
                // 4.1.1
                ['criterio_id' => $crit('4.1.1'), 'nomenclatura' => '300', 'descripcion' => 'Descripción de un reglamento de evaluación de los aprendizajes.'],
                ['criterio_id' => $crit('4.1.1'), 'nomenclatura' => '301', 'descripcion' => 'Descripción de reclamos más comunes de los estudiantes.'],
                ['criterio_id' => $crit('4.1.1'), 'nomenclatura' => '302', 'descripcion' => 'Opinión de los estudiantes sobre el grado de cumplimiento de la normativa de evaluación.'],
                // 4.1.2
                ['criterio_id' => $crit('4.1.2'), 'nomenclatura' => '303', 'descripcion' => 'Descripción del mecanismo que se sigue para registrar información sobre las características del estudiantado.'],
                ['criterio_id' => $crit('4.1.2'), 'nomenclatura' => '304', 'descripcion' => 'Estadísticas anuales disponibles sobre el estudiantado y sus características.'],
                ['criterio_id' => $crit('4.1.2'), 'nomenclatura' => '305', 'descripcion' => 'Descripción de la forma como se utilizan los datos acerca de las características del estudiantado en la toma de decisiones.'],
                // 4.1.3
                ['criterio_id' => $crit('4.1.3'), 'nomenclatura' => '306', 'descripcion' => 'Descripción del mecanismo que permite generar estadísticas de rendimiento estudiantil por curso, por docente y por nivel.'],
                ['criterio_id' => $crit('4.1.3'), 'nomenclatura' => '307', 'descripcion' => 'Descripción del mecanismo que permite generar estadísticas sobre tasas de aprobación, reprobación y deserción por curso, por docente y por nivel.'],
                // 4.1.4
                ['criterio_id' => $crit('4.1.4'), 'nomenclatura' => '308', 'descripcion' => 'Descripción de la normativa que regula los plazos de entrega de los resultados obtenidos por los estudiantes en los cursos.'],
                ['criterio_id' => $crit('4.1.4'), 'nomenclatura' => '309', 'descripcion' => 'Distribución porcentual de estudiantes que opinan tener acceso oportuno al rendimiento obtenido en los cursos matriculados.'],
                // 4.1.5
                ['criterio_id' => $crit('4.1.5'), 'nomenclatura' => '310', 'descripcion' => 'Distribución de los estudiantes matriculados según promedio anual obtenido en los últimos cuatro años por nivel.'],
                // 4.2.1
                ['criterio_id' => $crit('4.2.1'), 'nomenclatura' => '311', 'descripcion' => 'Descripción de requisitos de graduación establecidos.'],
                ['criterio_id' => $crit('4.2.1'), 'nomenclatura' => '312', 'descripcion' => 'Distribución de los estudiantes graduados en los últimos cuatro años, según la modalidad de trabajo final de graduación presentado.'],
                // 4.2.2
                ['criterio_id' => $crit('4.2.2'), 'nomenclatura' => '313', 'descripcion' => 'Razones que aducen los estudiantes activos para prolongar la duración de los estudios más allá de lo establecido.'],
                ['criterio_id' => $crit('4.2.2'), 'nomenclatura' => '314', 'descripcion' => 'Razones que aducen los graduados para haber durado más tiempo del establecido en obtener su título.'],
                // 4.2.3
                ['criterio_id' => $crit('4.2.3'), 'nomenclatura' => '315', 'descripcion' => 'Porcentaje de estudiantes que han cumplido con el Trabajo Comunal como requisito de graduación.'],
                ['criterio_id' => $crit('4.2.3'), 'nomenclatura' => '316', 'descripcion' => 'Total de horas establecidas para el trabajo comunal.'],
                ['criterio_id' => $crit('4.2.3'), 'nomenclatura' => '317', 'descripcion' => 'Ejemplos de acciones que realizan los estudiantes como parte del trabajo comunal universitario.'],
                ['criterio_id' => $crit('4.2.3'), 'nomenclatura' => '318', 'descripcion' => 'Descripción de otras acciones de proyección con que cuenta la carrera.'],
                ['criterio_id' => $crit('4.2.3'), 'nomenclatura' => '319', 'descripcion' => 'Total de personal académico y estudiantes que han participado en otras acciones de proyección en los últimos cuatro años.'],
                // 4.2.4
                ['criterio_id' => $crit('4.2.4'), 'nomenclatura' => '320', 'descripcion' => 'Descripción de los mecanismos que utiliza la carrera (por ejemplo, estudios, encuentros y foros) para obtener información sobre las condiciones del mercado laboral.'],
                ['criterio_id' => $crit('4.2.4'), 'nomenclatura' => '321', 'descripcion' => 'Descripción de las principales condiciones que han tenido el mercado laboral de la disciplina y sus graduados durante los últimos dos años.'],
                ['criterio_id' => $crit('4.2.4'), 'nomenclatura' => '322', 'descripcion' => 'Mecanismos que utiliza la carrera para informar a los estudiantes activos sobre las condiciones del mercado laboral.'],
                // 4.2.5
                ['criterio_id' => $crit('4.2.5'), 'nomenclatura' => '323', 'descripcion' => 'Descripción del mecanismo de información sobre los graduados de la carrera.'],
                ['criterio_id' => $crit('4.2.5'), 'nomenclatura' => '324', 'descripcion' => 'Estadísticas anuales disponibles sobre los graduados.'],
                // 4.2.6
                ['criterio_id' => $crit('4.2.6'), 'nomenclatura' => '325', 'descripcion' => 'Existencia de una base de datos con información de los graduados.'],
                ['criterio_id' => $crit('4.2.6'), 'nomenclatura' => '326', 'descripcion' => 'Descripción de los mecanismos que utiliza la carrera para dar seguimiento a los graduados y a su inserción en el mercado laboral.'],
                ['criterio_id' => $crit('4.2.6'), 'nomenclatura' => '327', 'descripcion' => 'Descripción de las maneras que utiliza la carrera para dar seguimiento y conocer sobre la inserción de graduados en el mercado.'],
                ['criterio_id' => $crit('4.2.6'), 'nomenclatura' => '328', 'descripcion' => 'Ejemplos de mejoras hechas al plan de estudios, originadas en la información obtenida del seguimiento y el contacto con los graduados.'],
                // 4.2.7
                ['criterio_id' => $crit('4.2.7'), 'nomenclatura' => '329', 'descripcion' => 'Percepción de los graduados sobre la formación recibida en los aspectos cognitivos, actitudinales, de destrezas y de competencias generales.'],
                ['criterio_id' => $crit('4.2.7'), 'nomenclatura' => '330', 'descripcion' => 'Porcentaje de graduados de los últimos cuatro años que opina que la formación recibida lo facultó para continuar aprendiendo en el campo de su especialidad y en las áreas en que ha continuado estudios.'],
                // 4.2.8
                ['criterio_id' => $crit('4.2.8'), 'nomenclatura' => '331', 'descripcion' => 'Porcentaje de graduados que opina que la preparación recibida le permite un desempeño profesional satisfactorio.'],
                ['criterio_id' => $crit('4.2.8'), 'nomenclatura' => '332', 'descripcion' => 'Grado en que los graduados aplican, en su ejercicio profesional, los conocimientos adquiridos en el período de formación universitaria, según la opinión que ellos ofrecen.'],
                // 4.2.9
                ['criterio_id' => $crit('4.2.9'), 'nomenclatura' => '333', 'descripcion' => 'Mecanismos establecidos para que los graduados mantengan vínculos con la carrera.'],
                ['criterio_id' => $crit('4.2.9'), 'nomenclatura' => '334', 'descripcion' => 'Descripción del grado de respuesta de los graduados a estas acciones.'],
                // 4.2.10
                ['criterio_id' => $crit('4.2.10'), 'nomenclatura' => '335', 'descripcion' => 'Frecuencia y tipo de oportunidades de actualización profesional que la carrera ofrece a los graduados.'],
                ['criterio_id' => $crit('4.2.10'), 'nomenclatura' => '336', 'descripcion' => 'Distribución porcentual de la opinión de los graduados sobre las oportunidades de actualización profesional disponibles.'],
                ['criterio_id' => $crit('4.2.10'), 'nomenclatura' => '337', 'descripcion' => 'Mecanismos para detectar las necesidades de actualización y educación continua de sus graduados.'],
                // 4.2.11
                ['criterio_id' => $crit('4.2.11'), 'nomenclatura' => '338', 'descripcion' => 'Porcentaje de empleadores que se muestran satisfechos con el desempeño y con el perfil de salida de los graduados de la carrera.'],
                // 4.3.1
                ['criterio_id' => $crit('4.3.1'), 'nomenclatura' => '339', 'descripcion' => 'Total de producción académica indexada según docente a tiempo completo en los últimos cuatro años.'],
                ['criterio_id' => $crit('4.3.1'), 'nomenclatura' => '340', 'descripcion' => 'Descripción y lista del tipo de productos generados a partir de las acciones de extensión.'],
                ['criterio_id' => $crit('4.3.1'), 'nomenclatura' => '341', 'descripcion' => 'Análisis de cómo los proyectos o acciones de extensión vigentes, a cargo de la carrera, brindan aportes a la comunidad nacional.'],
                ['criterio_id' => $crit('4.3.1'), 'nomenclatura' => '342', 'descripcion' => 'Descripción de otro tipo de producción académica.'],
                ['criterio_id' => $crit('4.3.1'), 'nomenclatura' => '343', 'descripcion' => 'Total de producción académica indexada según docente a tiempo parcial en los últimos cuatro años.'],
                ['criterio_id' => $crit('4.3.1'), 'nomenclatura' => '344', 'descripcion' => 'Nombre y fecha de eventos académicos según docente (ponencias o conferencias en foros nacionales o internacionales).'],

                // ── SOSTENIBILIDAD ────────────────────────────────────────────
                ['criterio_id' => $crit('S.1'), 'nomenclatura' => '345', 'descripcion' => 'Descripción de las políticas, mecanismos y lineamientos institucionales y de la carrera citados en los criterios S.1 a S.5 (nivel universidad).'],
                ['criterio_id' => $crit('S.3'), 'nomenclatura' => '346', 'descripcion' => 'Descripción de elementos que demuestren la puesta en práctica de dichas políticas, mecanismos y lineamientos a nivel universitario.'],
                ['criterio_id' => $crit('S.6'), 'nomenclatura' => '347', 'descripcion' => 'Documento oficial aprobado con las políticas, mecanismos y lineamientos de la carrera citados en los criterios S.6 a S.10.'],
                ['criterio_id' => $crit('S.8'), 'nomenclatura' => '348', 'descripcion' => 'Descripción de elementos que demuestren la puesta en práctica de dichas políticas, mecanismos y lineamientos a nivel de carrera.'],
            ];

            foreach ($evidencias as &$e) {
                $e['estado'] = 'Pendiente';
                $e['activo'] = true;
                $e['created_at'] = now();
                $e['updated_at'] = now();
            }
            unset($e);
            DB::table('EVIDENCIA')->insert($evidencias);
            $this->command->info('  ✓ 348 Evidencias');
        } else {
            $this->command->warn('  ℹ  EVIDENCIA ya tiene datos — omitiendo inserción.');
        }

        $this->command->info('✅ TraditionalStructureSeeder completado.');
        $this->command->info('   Estructura: 5 Dimensiones → 21 Componentes → 171 Criterios → 34 Estándares → 348 Evidencias');
    }
}
