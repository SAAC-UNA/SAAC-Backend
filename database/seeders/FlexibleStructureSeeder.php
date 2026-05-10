<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FlexibleStructureSeeder extends Seeder
{
    // -------------------------------------------------------------------------
    // SINAES 2025 — Modelo de Acreditación Carreras de Grado
    // Fuente oficial: "Pautas de Evaluación para la Acreditación de Carreras de
    // Grado que se imparten en la modalidad presencial y no presencial" (2025)
    // Sesión 1887-2025 del 16 de setiembre del 2025.
    // -------------------------------------------------------------------------

    private function data(): array
    {
        return [

            // -----------------------------------------------------------------
            // NIVEL 1: Dimensiones
            // -----------------------------------------------------------------
            'dimensions' => [
                [
                    'code' => 'D1',
                    'name' => 'Plan de Estudios',
                    'description' => 'Corresponde a la propuesta formativa de la carrera, tanto en lo concerniente a la definición de las competencias o capacidades que se espera desarrollar en el estudiantado, como a la propuesta curricular para lograrlas.',
                ],
                [
                    'code' => 'D2',
                    'name' => 'Proceso educativo',
                    'description' => 'Corresponde al conjunto de metodologías de enseñanza y evaluación para los aprendizajes, actividades y orientaciones conducentes al logro del perfil académico profesional definido por la carrera.',
                ],
                [
                    'code' => 'D3',
                    'name' => 'Estudiantes y personas graduadas',
                    'description' => 'Se refiere a la población estudiantil que, de manera sistemática, sigue o ha seguido el plan de estudios de la carrera, así como a las acciones que esta realiza para la atención integral del estudiantado.',
                ],
                [
                    'code' => 'D4',
                    'name' => 'Personal académico, administrativo, técnico y de apoyo',
                    'description' => 'Se refiere al equipo de personas que trabajan en la formación teórica y práctica del estudiantado y a quienes llevan a cabo las acciones administrativas y de apoyo necesarias para que todos los elementos se dispongan correcta y oportunamente.',
                ],
                [
                    'code' => 'D5',
                    'name' => 'Recursos y servicios de apoyo',
                    'description' => 'Abarca el conjunto de recursos y servicios que la institución ofrece para ejecutar las acciones de la carrera (edificios, talleres, laboratorios, equipo tecnológico y mobiliario).',
                ],
                [
                    'code' => 'D6',
                    'name' => 'Gestión de la carrera',
                    'description' => 'Se refiere a la interrelación estratégica entre lo normativo, administrativo, académico y sus actores para lograr los propósitos de la carrera.',
                ],
            ],

            // -----------------------------------------------------------------
            // NIVEL 2: Pautas  (59 en total)
            // -----------------------------------------------------------------
            'pautas' => [

                // ── DIMENSIÓN 1: Plan de Estudios (pautas 1–12) ──────────────
                ['num' => 1,  'dimension' => 1, 'category' => 'A', 'description' => 'Los programas de los cursos de la malla curricular vigente son coherentes con el perfil académico profesional de la carrera (sustentado en los referentes nacionales e internacionales actuales, el desarrollo y las tendencias de la disciplina o de la profesión).'],
                ['num' => 2,  'dimension' => 1, 'category' => 'A', 'description' => 'El perfil o requisitos de ingreso requeridos son coherentes con los resultados de aprendizaje/objetivos de aprendizaje esperados para los grados.'],
                ['num' => 3,  'dimension' => 1, 'category' => 'A', 'description' => 'Las estrategias y metodologías de enseñanza y de evaluación para los aprendizajes son pertinentes para lograr el perfil académico profesional.'],
                ['num' => 4,  'dimension' => 1, 'category' => 'A', 'description' => 'La dedicación académica estudiantil requerida en cada una de las asignaturas es suficiente para lograr los aprendizajes previstos.'],
                ['num' => 5,  'dimension' => 1, 'category' => 'A', 'description' => 'Las actividades de aprendizaje son pertinentes para lograr los resultados/objetivos de aprendizaje de los cursos.'],
                ['num' => 6,  'dimension' => 1, 'category' => 'A', 'description' => 'La carrera cuenta con procedimientos eficaces de revisión y actualización periódica del plan de estudios al menos cada cuatro años, donde incorpora la participación del personal académico, estudiantado, personas graduadas y empleadores.'],
                ['num' => 7,  'dimension' => 1, 'category' => 'D', 'description' => 'La carrera desarrolla actividades académicas extracurriculares pertinentes para la formación que imparte y el desarrollo de habilidades para la vida.'],
                ['num' => 8,  'dimension' => 1, 'category' => 'B', 'description' => 'La carrera en sus estrategias pedagógicas incorpora recursos didácticos idóneos que le permiten al estudiantado apropiarse de contenidos disciplinares relevantes y actualizados en una segunda lengua.'],
                ['num' => 9,  'dimension' => 1, 'category' => 'B', 'description' => 'La carrera promueve con eficacia el desarrollo de competencias digitales tales como: gestión de la información, comunicación digital, trabajo colaborativo virtual, visión digital estratégica, liderazgo digital, seguridad digital y resolución de problemas técnicos, ofimática y edición de contenidos.'],
                ['num' => 10, 'dimension' => 1, 'category' => 'A', 'description' => 'El plan de estudios de la carrera promueve elementos pertinentes para el desarrollo de competencias de indagación y razonamiento científico en el estudiantado.'],
                ['num' => 11, 'dimension' => 1, 'category' => 'A', 'description' => 'La carrera cuenta con políticas, recursos y estrategias para promover en el personal académico el desarrollo de proyectos, actividades y acciones de investigación, extensión, innovación, diseño y producción artística, según corresponda.'],
                ['num' => 12, 'dimension' => 1, 'category' => 'B', 'description' => 'La investigación educativa y científica, la extensión, la innovación, el diseño y la producción artística, según corresponda, mejoran e impulsan en forma eficaz el pensamiento crítico, creativo, innovador y científico en el personal académico y el estudiantado.'],

                // ── DIMENSIÓN 2: Proceso educativo (pautas 13–21) ────────────
                ['num' => 13, 'dimension' => 2, 'category' => 'A', 'description' => 'Las estrategias de mediación pedagógica aplicadas en la carrera son coherentes con la naturaleza de la disciplina, el modelo educativo de la institución y la modalidad de los cursos.'],
                ['num' => 14, 'dimension' => 2, 'category' => 'B', 'description' => 'La carrera implementa una mediación pedagógica oportuna para desarrollar las capacidades definidas en el perfil académico profesional.'],
                ['num' => 15, 'dimension' => 2, 'category' => 'D', 'description' => 'La carrera cuenta con procedimientos eficaces que garanticen la asesoría y supervisión al estudiantado en las diferentes modalidades de graduación y durante su práctica profesional.'],
                ['num' => 16, 'dimension' => 2, 'category' => 'A', 'description' => 'Las actividades de evaluación para los aprendizajes promueven la equidad, mediante estrategias alternativas para todo el estudiantado.'],
                ['num' => 17, 'dimension' => 2, 'category' => 'B', 'description' => 'La carrera le brinda a cada estudiante realimentación con eficacia sobre los resultados de sus evaluaciones para el aprendizaje.'],
                ['num' => 18, 'dimension' => 2, 'category' => 'B', 'description' => 'Los resultados de la evaluación para los aprendizajes se utilizan de manera eficaz en el mejoramiento de los procesos pedagógicos.'],
                ['num' => 19, 'dimension' => 2, 'category' => 'B', 'description' => 'Los recursos didácticos (físicos y virtuales) que utiliza la carrera para el desarrollo de las actividades de aprendizaje son pertinentes, de acuerdo con la modalidad en que se imparte.'],
                ['num' => 20, 'dimension' => 2, 'category' => 'B', 'description' => 'Las técnicas e instrumentos de evaluación para los aprendizajes son coherentes con los objetivos del aprendizaje y las estrategias de mediación pedagógica de los cursos.'],
                ['num' => 21, 'dimension' => 2, 'category' => 'C', 'description' => 'La carrera cuenta con mecanismos eficaces para garantizar que los instrumentos de evaluación estén técnicamente elaborados.'],

                // ── DIMENSIÓN 3: Estudiantes y personas graduadas (22–28) ────
                ['num' => 22, 'dimension' => 3, 'category' => 'B', 'description' => 'La carrera desarrolla procesos pertinentes de inducción al estudiantado de nuevo ingreso de acuerdo con los requerimientos de la modalidad en que se imparte (incluye la inducción al uso de plataformas virtuales en caso de ser utilizadas).'],
                ['num' => 23, 'dimension' => 3, 'category' => 'C', 'description' => 'La universidad o la carrera pone a disposición del estudiantado servicios de apoyo pertinentes para su desarrollo cognitivo, afectivo y social.'],
                ['num' => 24, 'dimension' => 3, 'category' => 'B', 'description' => 'La carrera proporciona, de manera pública y accesible, información pertinente acerca del perfil académico profesional y plan de estudios vigente, requisitos de ingreso y recursos tecnológicos necesarios para llevar a cabo el proceso de enseñanza-aprendizaje.'],
                ['num' => 25, 'dimension' => 3, 'category' => 'B', 'description' => 'La carrera sigue el avance del estudiantado mediante estadísticas y procedimientos eficaces y propone acciones para potenciar la progresión oportuna y disminuir la repitencia, el rezago y deserción, incluido un plan de nivelación de quienes procedan de bachilleratos universitarios distintos a la disciplina en la cual se ubica la licenciatura (para carreras que solo ofrecen el tramo de licenciatura y reciben bachilleres de varias carreras afines o no).'],
                ['num' => 26, 'dimension' => 3, 'category' => 'D', 'description' => 'La carrera cuenta con una normativa institucional y espacios idóneos que garantizan la organización, participación y representación estudiantil en los escenarios correspondientes.'],
                ['num' => 27, 'dimension' => 3, 'category' => 'C', 'description' => 'La carrera cuenta con un sistema de información que le permite obtener datos relevantes para el seguimiento a las personas graduadas y estos son considerados en la toma de decisiones.'],
                ['num' => 28, 'dimension' => 3, 'category' => 'C', 'description' => 'La carrera ofrece a sus graduados oportunidades relevantes de educación continua.'],

                // ── DIMENSIÓN 4: Personal (29–40) ─────────────────────────────
                ['num' => 29, 'dimension' => 4, 'category' => 'A', 'description' => 'El personal académico de la carrera es idóneo para atender los requerimientos del plan de estudios vigente (incluyendo las habilidades y las destrezas para la mediación pedagógica) de acuerdo con la modalidad en que se imparte la carrera.'],
                ['num' => 30, 'dimension' => 4, 'category' => 'B', 'description' => 'El personal académico de la carrera es suficiente para atender la gestión, docencia, trabajos finales de graduación, investigación y extensión.'],
                ['num' => 31, 'dimension' => 4, 'category' => 'B', 'description' => 'La carrera mantiene relaciones académicas activas pertinentes con centros, grupos, redes o programas dedicados a la investigación científica y educativa, extensión, innovación, diseño y producción artística en su campo disciplinar.'],
                ['num' => 32, 'dimension' => 4, 'category' => 'B', 'description' => 'La carrera promueve y ejecuta con eficacia la divulgación de la producción académica (resultados de investigación, extensión, innovación, producción artística y trabajos finales de graduación según corresponda) del personal académico y del estudiantado.'],
                ['num' => 33, 'dimension' => 4, 'category' => 'D', 'description' => 'El personal administrativo, técnico y de apoyo de la carrera es suficiente para atender sus necesidades de gestión.'],
                ['num' => 34, 'dimension' => 4, 'category' => 'D', 'description' => 'El personal administrativo, técnico y de apoyo de la carrera es idóneo para atender sus necesidades de gestión.'],
                ['num' => 35, 'dimension' => 4, 'category' => 'C', 'description' => 'La carrera ejecuta con equidad los procesos de selección, contratación, evaluación y promoción del personal académico, administrativo, técnico y de apoyo.'],
                ['num' => 36, 'dimension' => 4, 'category' => 'B', 'description' => 'La carrera desarrolla procesos pertinentes de inducción para el personal académico, administrativo, técnico y de apoyo de nuevo ingreso de acuerdo con los requerimientos de la modalidad en que se imparte la carrera.'],
                ['num' => 37, 'dimension' => 4, 'category' => 'B', 'description' => 'El personal académico cuenta con suficiente equipo, accesibilidad y conectividad para llevar a cabo las actividades de aprendizaje, comunicación y atención tutorial y de consultas, de acuerdo con los requerimientos de la modalidad en que se imparte la carrera.'],
                ['num' => 38, 'dimension' => 4, 'category' => 'B', 'description' => 'La carrera ejecuta acciones eficaces de mejoramiento en los métodos y técnicas de enseñanza y evaluación para los aprendizajes de acuerdo con los requerimientos de la modalidad en que se imparte.'],
                ['num' => 39, 'dimension' => 4, 'category' => 'C', 'description' => 'La universidad o la carrera realiza o facilita procesos pertinentes de desarrollo profesional y actualización para el personal académico, administrativo, técnico y de apoyo.'],
                ['num' => 40, 'dimension' => 4, 'category' => 'A', 'description' => 'La carrera cuenta con mecanismos eficaces para la evaluación del desempeño del personal académico, administrativo, técnico y de apoyo y los implementa de manera sistemática para la toma de decisiones.'],

                // ── DIMENSIÓN 5: Recursos y servicios de apoyo (41–45) ────────
                ['num' => 41, 'dimension' => 5, 'category' => 'B', 'description' => 'La carrera dispone de la infraestructura física y tecnológica suficiente, según los requerimientos de la modalidad en que se imparte.'],
                ['num' => 42, 'dimension' => 5, 'category' => 'C', 'description' => 'La universidad o la carrera cuenta con una normativa pertinente referida a higiene, seguridad y salud ocupacional, accesibilidad, prevención de riesgos y atención a desastres naturales.'],
                ['num' => 43, 'dimension' => 5, 'category' => 'C', 'description' => 'La universidad o la carrera cuenta con políticas pertinentes y realiza acciones para promover la equidad de género.'],
                ['num' => 44, 'dimension' => 5, 'category' => 'D', 'description' => 'La carrera gestiona eficazmente la demanda de nuevos recursos, el mantenimiento y reemplazo de infraestructura y equipos.'],
                ['num' => 45, 'dimension' => 5, 'category' => 'C', 'description' => 'La universidad o la carrera cuenta con la normativa eficaz y ejecuta acciones para prevenir y atender el hostigamiento sexual.'],

                // ── DIMENSIÓN 6: Gestión de la carrera (46–59) ───────────────
                ['num' => 46, 'dimension' => 6, 'category' => 'C', 'description' => 'La persona que dirige la carrera es idónea para el puesto en tanto posee habilidades gerenciales, administrativas, académicas y de liderazgo.'],
                ['num' => 47, 'dimension' => 6, 'category' => 'B', 'description' => 'Los servicios administrativos y académicos (asincrónicos, sincrónicos y presenciales) que ofrece la carrera atienden de manera eficaz las necesidades del usuario presencial y no presencial.'],
                ['num' => 48, 'dimension' => 6, 'category' => 'A', 'description' => 'La carrera cuenta con un plan estratégico prospectivo y planes operativos pertinentes para sustentar la ejecución de los procesos educativos y la mejora continua.'],
                ['num' => 49, 'dimension' => 6, 'category' => 'B', 'description' => 'La carrera y sus actividades no presenciales están respaldadas por una unidad de gestión y soporte informático eficiente que incluya: servicio a personas usuarias, capacidad de almacenamiento, redundancia, encriptación y ciberseguridad, fluidez de la comunicación y adquisición de licencias según sus necesidades, sistema centralizado 24/7.'],
                ['num' => 50, 'dimension' => 6, 'category' => 'B', 'description' => 'La carrera cuenta con un campus virtual idóneo para albergar las actividades virtuales de la oferta académica: interfaz amigable, responsividad, adaptabilidad, control de acceso, perfiles de usuario, almacenamiento de información de cursos, replicabilidad y accesibilidad.'],
                ['num' => 51, 'dimension' => 6, 'category' => 'B', 'description' => 'El campus virtual permite una gestión académica idónea de las actividades de aprendizaje no presenciales.'],
                ['num' => 52, 'dimension' => 6, 'category' => 'C', 'description' => 'La carrera cuenta con sistemas de información eficientes para apoyar el proceso de la gestión y toma de decisiones.'],
                ['num' => 53, 'dimension' => 6, 'category' => 'B', 'description' => 'La carrera implementa procedimientos eficaces de verificación y resguardo de la información referida a los procesos académicos y a la declaración de cumplimiento de los requisitos de graduación.'],
                ['num' => 54, 'dimension' => 6, 'category' => 'B', 'description' => 'La carrera implementa mecanismos eficaces para la sostenibilidad de su calidad.'],
                ['num' => 55, 'dimension' => 6, 'category' => 'C', 'description' => 'La carrera proporciona, de manera transparente, información acerca de: normativa interna, trámites académicos y administrativos, plan de estudios, convenios e incentivos, y servicios (salud, biblioteca, formación docente y apoyo a personal académico, administrativo, técnico, de apoyo y estudiantes).'],
                ['num' => 56, 'dimension' => 6, 'category' => 'A', 'description' => 'La universidad o la carrera sigue de manera eficaz los principios de la educación inclusiva y cuenta con los recursos para lograrlo.'],
                ['num' => 57, 'dimension' => 6, 'category' => 'C', 'description' => 'La carrera pone en práctica mecanismos eficaces de vinculación con el sector social, público y productivos afines a la carrera, así como mecanismos de seguimiento y evaluación de las actividades que emprende con dichos sectores.'],
                ['num' => 58, 'dimension' => 6, 'category' => 'D', 'description' => 'La universidad o la carrera realiza acciones eficaces para incorporar la dimensión ambiental a su proyecto institucional, educativo y laboral.'],
                ['num' => 59, 'dimension' => 6, 'category' => 'C', 'description' => 'La carrera gestiona actividades académicas pertinentes en procura de su internacionalización.'],
            ],

            // -----------------------------------------------------------------
            // NIVEL 3: Fuentes de información (137 en total)
            // -----------------------------------------------------------------
            'fuentes' => [

                // ── Pauta 1 ──────────────────────────────────────────────────
                ['num' => 1,  'pauta' => 1,  'description' => 'Perfil académico profesional (plan de estudios vigente).'],
                ['num' => 2,  'pauta' => 1,  'description' => 'Malla curricular.'],
                ['num' => 3,  'pauta' => 1,  'description' => 'Programas de cursos (habilidades, destrezas, competencias en relación con el perfil académico profesional).'],

                // ── Pauta 2 ──────────────────────────────────────────────────
                ['num' => 4,  'pauta' => 2,  'description' => 'Plan de estudios vigente.'],
                ['num' => 5,  'pauta' => 2,  'description' => 'Perfil o requisitos de ingreso.'],

                // ── Pauta 3 ──────────────────────────────────────────────────
                ['num' => 6,  'pauta' => 3,  'description' => 'Modelo educativo de la universidad.'],
                ['num' => 7,  'pauta' => 3,  'description' => 'Perfil académico profesional (Plan de estudio vigente).'],
                ['num' => 8,  'pauta' => 3,  'description' => 'Tabla con el perfil académico profesional, las estrategias de enseñanza y las estrategias de evaluación.'],
                ['num' => 9,  'pauta' => 3,  'description' => 'Programas de los cursos.'],
                ['num' => 10, 'pauta' => 3,  'description' => 'Opinión de las personas graduadas de los últimos cuatro años sobre si las capacidades desarrolladas gracias a las estrategias y metodologías de enseñanza y de evaluación fueron pertinentes para alcanzar el perfil académico profesional.'],

                // ── Pauta 4 ──────────────────────────────────────────────────
                ['num' => 11, 'pauta' => 4,  'description' => 'Malla curricular.'],
                ['num' => 12, 'pauta' => 4,  'description' => 'Cuadro con la indicación de las asignaturas de la carrera y horas dedicadas en cada una de ellas a teoría, prácticas de laboratorio, trabajo independiente, actividades extra clase, entre otros, según corresponda.'],

                // ── Pauta 5 ──────────────────────────────────────────────────
                ['num' => 13, 'pauta' => 5,  'description' => 'Programa de los cursos.'],
                ['num' => 14, 'pauta' => 5,  'description' => 'Cuadro indicando curso, actividades de aprendizaje, objetivo o resultado del aprendizaje referido en el programa del curso.'],

                // ── Pauta 6 ──────────────────────────────────────────────────
                ['num' => 15, 'pauta' => 6,  'description' => 'Reglamentos o normativas académicas pertinentes.'],
                ['num' => 16, 'pauta' => 6,  'description' => 'Cuadro comparativo en el que consten los cambios realizados en el plan de estudios en los últimos cuatro años.'],
                ['num' => 17, 'pauta' => 6,  'description' => 'Lista de actividades realizadas y participantes (personal académico, estudiantado, personas graduadas y empleadores) para la revisión y actualización del plan de estudios.'],

                // ── Pauta 7 ──────────────────────────────────────────────────
                ['num' => 18, 'pauta' => 7,  'description' => 'Plan de estudios de la carrera o documento en el que consten las actividades extracurriculares que desarrolla la carrera.'],

                // ── Pauta 8 ──────────────────────────────────────────────────
                ['num' => 19, 'pauta' => 8,  'description' => 'Programas de cursos y otras actividades que incorporan recursos didácticos en una segunda lengua.'],
                ['num' => 20, 'pauta' => 8,  'description' => 'Opinión del estudiantado sobre la idoneidad de los recursos didácticos en una segunda lengua.'],

                // ── Pauta 9 ──────────────────────────────────────────────────
                ['num' => 21, 'pauta' => 9,  'description' => 'Matriz que asocia la competencia digital, la norma de seguridad y el código de comportamiento que se desarrollan en cada uno de los cursos.'],
                ['num' => 22, 'pauta' => 9,  'description' => 'Opinión del estudiantado, personas graduadas, empleadores sobre la eficacia de la carrera en el desarrollo de las competencias digitales.'],

                // ── Pauta 10 ─────────────────────────────────────────────────
                ['num' => 23, 'pauta' => 10, 'description' => 'Plan de estudios (descripción de las asignaturas y su aporte al desarrollo de las competencias de indagación y pensamiento científico).'],

                // ── Pauta 11 ─────────────────────────────────────────────────
                ['num' => 24, 'pauta' => 11, 'description' => 'Políticas y estrategias establecidas.'],
                ['num' => 25, 'pauta' => 11, 'description' => 'Listas de proyectos, actividades y acciones de investigación, extensión, innovación, diseño y producción artística, según corresponda, realizadas en los últimos cuatro años y número de participantes.'],
                ['num' => 26, 'pauta' => 11, 'description' => 'Opinión del personal académico sobre la eficiencia de políticas, recursos y estrategias para promover proyectos, actividades y acciones de investigación, extensión, innovación, diseño y producción artística, según corresponda.'],

                // ── Pauta 12 ─────────────────────────────────────────────────
                ['num' => 27, 'pauta' => 12, 'description' => 'Lista de proyectos, actividades, programas y acciones de investigación científica y educativa, innovación, diseño, producción artística y extensión, trabajos de graduación y en ejecución o finalizados en los últimos cuatro años.'],

                // ── Pauta 13 ─────────────────────────────────────────────────
                ['num' => 28, 'pauta' => 13, 'description' => 'Matriz con el eje pedagógico del modelo educativo, la naturaleza de la disciplina y las estrategias de mediación pedagógica que utiliza la carrera.'],

                // ── Pauta 14 ─────────────────────────────────────────────────
                ['num' => 29, 'pauta' => 14, 'description' => 'Perfil académico profesional.'],
                ['num' => 30, 'pauta' => 14, 'description' => 'Programas de cursos.'],
                ['num' => 31, 'pauta' => 14, 'description' => 'Lista de estrategias de mediación pedagógica.'],

                // ── Pauta 15 ─────────────────────────────────────────────────
                ['num' => 32, 'pauta' => 15, 'description' => 'Reglamento de Trabajos Finales de Graduación.'],
                ['num' => 33, 'pauta' => 15, 'description' => 'Opinión de los graduados y egresados de los últimos dos años acerca de la asesoría y supervisión recibida para la elaboración de su trabajo final de graduación y durante su práctica profesional.'],

                // ── Pauta 16 ─────────────────────────────────────────────────
                ['num' => 34, 'pauta' => 16, 'description' => 'Estrategias de evaluación alternativa que utiliza la carrera.'],
                ['num' => 35, 'pauta' => 16, 'description' => 'Ejemplos de instrumentos para la evaluación de los aprendizajes que utiliza la carrera.'],
                ['num' => 36, 'pauta' => 16, 'description' => 'Reglamentación sobre la evaluación de actividades de aprendizaje.'],
                ['num' => 37, 'pauta' => 16, 'description' => 'Opinión del estudiantado acerca de la equidad en las actividades de evaluación.'],

                // ── Pauta 17 ─────────────────────────────────────────────────
                ['num' => 38, 'pauta' => 17, 'description' => 'Normativa de evaluación para los aprendizajes del estudiantado, incluida la práctica profesional.'],
                ['num' => 39, 'pauta' => 17, 'description' => 'Opinión del estudiantado acerca de si reciben realimentación eficaz en torno a los resultados de las evaluaciones que realizan.'],

                // ── Pauta 18 ─────────────────────────────────────────────────
                ['num' => 40, 'pauta' => 18, 'description' => 'Lineamiento, mecanismo o instrumento que utiliza la carrera para asegurar el mejoramiento de los procesos pedagógicos, incluida la práctica profesional, según los resultados de las evaluaciones del estudiantado.'],

                // ── Pauta 19 ─────────────────────────────────────────────────
                ['num' => 41, 'pauta' => 19, 'description' => 'Descripción de cómo se respetan los códigos éticos y legales de uso.'],
                ['num' => 42, 'pauta' => 19, 'description' => 'Descripción de los mecanismos que utiliza la carrera para asegurar la accesibilidad de los materiales y recursos didácticos.'],
                ['num' => 43, 'pauta' => 19, 'description' => 'Opinión del estudiantado sobre la pertinencia de los materiales y recursos didácticos (físicos y virtuales) utilizados en los cursos de la carrera.'],

                // ── Pauta 20 ─────────────────────────────────────────────────
                ['num' => 44, 'pauta' => 20, 'description' => 'Programas de los cursos.'],
                ['num' => 45, 'pauta' => 20, 'description' => 'Opinión del estudiantado sobre la coherencia entre los objetivos de aprendizaje y la mediación pedagógica desarrollada.'],
                ['num' => 46, 'pauta' => 20, 'description' => 'Opinión del estudiantado sobre la coherencia entre la mediación pedagógica desarrollada y la evaluación para los aprendizajes.'],

                // ── Pauta 21 ─────────────────────────────────────────────────
                ['num' => 47, 'pauta' => 21, 'description' => 'Descripción de las acciones que realiza la carrera para garantizar que los instrumentos de evaluación estén técnicamente elaborados.'],

                // ── Pauta 22 ─────────────────────────────────────────────────
                ['num' => 48, 'pauta' => 22, 'description' => 'Listado de acciones de inducción al estudiantado de nuevo ingreso de los últimos dos años, que favorezcan el proceso de transición a la educación universitaria de acuerdo con los requerimientos de la modalidad en que se imparte la carrera.'],
                ['num' => 49, 'pauta' => 22, 'description' => 'Registros de la participación en actividades de inducción: fotografías, videos, listas, memorias gráficas.'],
                ['num' => 50, 'pauta' => 22, 'description' => 'Acciones de acompañamiento experto y técnico a las actividades de inducción para el uso del campus virtual.'],
                ['num' => 51, 'pauta' => 22, 'description' => 'Opinión del estudiantado de nuevo ingreso sobre la pertinencia de las acciones de inducción de los últimos dos años para favorecer el proceso de transición a la educación universitaria de acuerdo con los requerimientos de la modalidad en que se imparte la carrera.'],

                // ── Pauta 23 ─────────────────────────────────────────────────
                ['num' => 52, 'pauta' => 23, 'description' => 'Lista de los servicios de apoyo para el desarrollo cognitivo, afectivo y social a los que tiene acceso el estudiantado de la carrera, con indicación del horario y oferta de servicios.'],
                ['num' => 53, 'pauta' => 23, 'description' => 'Opinión del estudiantado sobre la pertinencia de los servicios de apoyo que recibe para su desarrollo integral (cognitivo, afectivo y social).'],

                // ── Pauta 24 ─────────────────────────────────────────────────
                ['num' => 54, 'pauta' => 24, 'description' => 'Lista de medios de difusión (físicos o virtuales) utilizados para proporcionar la información acerca del perfil académico profesional y plan de estudios vigente, requisitos, condiciones de ingreso y recursos tecnológicos y de otra naturaleza necesarios para llevar a cabo el proceso de enseñanza-aprendizaje.'],
                ['num' => 55, 'pauta' => 24, 'description' => 'Opinión del estudiantado acerca de la disponibilidad y pertinencia de la información acerca del perfil académico profesional y plan de estudios vigente.'],
                ['num' => 56, 'pauta' => 24, 'description' => 'Opinión del estudiantado acerca de la disponibilidad y pertinencia de la información sobre los recursos tecnológicos necesarios para llevar a cabo el proceso de enseñanza-aprendizaje.'],

                // ── Pauta 25 ─────────────────────────────────────────────────
                ['num' => 57, 'pauta' => 25, 'description' => 'Datos de tasas de aprobación, repitencia, deserción y tiempo promedio de graduación de la última cohorte.'],
                ['num' => 58, 'pauta' => 25, 'description' => 'Descripción de las acciones ejecutadas para evitar la repitencia, el rezago y la deserción estudiantil en la última cohorte, plan de nivelación (atención al estudiantado, flexibilidad curricular, cursos electivos, diversificación de modalidades de trabajos finales de graduación, oferta oportuna de cursos, orientación vocacional, becas, centros de estudio, entre otras acciones).'],

                // ── Pauta 26 ─────────────────────────────────────────────────
                ['num' => 59, 'pauta' => 26, 'description' => 'Normativa institucional que faculta la organización estudiantil.'],
                ['num' => 60, 'pauta' => 26, 'description' => 'Opinión del estudiantado sobre la idoneidad de la normativa institucional y de los espacios para su organización, participación y representación en los escenarios correspondientes.'],

                // ── Pauta 27 ─────────────────────────────────────────────────
                ['num' => 61, 'pauta' => 27, 'description' => 'Instrumento de seguimiento a graduados.'],
                ['num' => 62, 'pauta' => 27, 'description' => 'Descripción de las acciones que incorporan la información aportada por las personas graduadas.'],

                // ── Pauta 28 ─────────────────────────────────────────────────
                ['num' => 63, 'pauta' => 28, 'description' => 'Listado de las actividades de educación continua, realizadas por la carrera en los últimos dos años, indicando temáticas abordadas y cantidad de participantes.'],
                ['num' => 64, 'pauta' => 28, 'description' => 'Opinión de las personas graduadas en los últimos dos años sobre la relevancia de la educación continua de la carrera para el ejercicio y actualización profesional.'],

                // ── Pauta 29 ─────────────────────────────────────────────────
                ['num' => 65, 'pauta' => 29, 'description' => 'Currículo vitae del personal académico.'],
                ['num' => 66, 'pauta' => 29, 'description' => 'Cuadro con datos del personal académico indicando grados académicos y experiencia profesional y académica (en docencia, extensión e investigación), producción académica, administración académica y otros atestados relevantes de acuerdo con la modalidad en que se imparte la carrera, de los últimos cuatro años.'],
                ['num' => 67, 'pauta' => 29, 'description' => 'Instrumento de evaluación del desempeño aplicado al personal académico.'],
                ['num' => 68, 'pauta' => 29, 'description' => 'Resultados de la evaluación del desempeño más reciente aplicada al personal académico de la carrera (no es necesario indicar los nombres).'],
                ['num' => 69, 'pauta' => 29, 'description' => 'Opinión del estudiantado acerca de la idoneidad de las habilidades, destrezas y la experiencia del personal académico para la mediación pedagógica en la modalidad en la que se imparte la carrera.'],

                // ── Pauta 30 ─────────────────────────────────────────────────
                ['num' => 70, 'pauta' => 30, 'description' => 'Jornada de contratación de la persona que ocupa la dirección de la carrera.'],
                ['num' => 71, 'pauta' => 30, 'description' => 'Listado de todo el personal académico con su jornada de contratación, indicando tiempo dedicado a docencia, investigación, extensión y administración académica.'],
                ['num' => 72, 'pauta' => 30, 'description' => 'Lista de acciones de docencia, investigación, acción social y gestión que realizan las personas docentes.'],
                ['num' => 73, 'pauta' => 30, 'description' => 'Opinión del personal académico sobre la suficiencia de las horas asignadas a docencia, investigación, extensión y administración académica para cumplir con las tareas asociadas a dicha labor.'],

                // ── Pauta 31 ─────────────────────────────────────────────────
                ['num' => 74, 'pauta' => 31, 'description' => 'Cuadro en el cual se indican los centros, grupos, redes o programas dedicados a la investigación científica y educativa, extensión, innovación, diseño y producción artística y los productos obtenidos y acciones ejecutadas en cada caso en los últimos cuatro años.'],

                // ── Pauta 32 ─────────────────────────────────────────────────
                ['num' => 75, 'pauta' => 32, 'description' => 'Cuadro con los resultados de la producción académica, personas autoras/participantes y medio de divulgación utilizado en los últimos cuatro años.'],
                ['num' => 76, 'pauta' => 32, 'description' => 'Opinión del personal académico y del estudiantado sobre la eficacia de la carrera para divulgar la producción académica relacionada con la disciplina.'],

                // ── Pauta 33 ─────────────────────────────────────────────────
                ['num' => 77, 'pauta' => 33, 'description' => 'Lista del personal administrativo, técnico y de apoyo de la carrera con indicación general de su función y tiempo asignado.'],
                ['num' => 78, 'pauta' => 33, 'description' => 'Opinión del personal académico y del estudiantado de la carrera sobre la suficiencia del personal administrativo para desarrollar sus funciones.'],

                // ── Pauta 34 ─────────────────────────────────────────────────
                ['num' => 79, 'pauta' => 34, 'description' => 'El perfil del puesto y los atestados del personal administrativo, técnico y de apoyo que atiende la carrera.'],

                // ── Pauta 35 ─────────────────────────────────────────────────
                ['num' => 80, 'pauta' => 35, 'description' => 'Políticas de gestión de recursos humanos y/o políticas de equidad.'],
                ['num' => 81, 'pauta' => 35, 'description' => 'Opinión del personal académico, administrativo, técnico y de apoyo sobre si los procesos de promoción, selección, contratación y evaluación se realizan con equidad.'],

                // ── Pauta 36 ─────────────────────────────────────────────────
                ['num' => 82, 'pauta' => 36, 'description' => 'Listado de acciones de inducción en las que ha participado el personal académico, administrativo, técnico y de apoyo de nuevo ingreso en los últimos dos años de acuerdo con los requerimientos de la modalidad en que se imparte la carrera.'],
                ['num' => 83, 'pauta' => 36, 'description' => 'Opinión del personal académico, administrativo, técnico y de apoyo que ha ingresado a la carrera en los últimos dos años sobre la existencia, pertinencia y calidad del proceso de inducción.'],

                // ── Pauta 37 ─────────────────────────────────────────────────
                ['num' => 84, 'pauta' => 37, 'description' => 'Lista de equipo disponible para que el personal académico pueda llevar a cabo las actividades de aprendizaje, comunicación y atención tutorial y de consultas, de acuerdo con los requerimientos de la modalidad en que se imparte la carrera.'],
                ['num' => 85, 'pauta' => 37, 'description' => 'Opinión del personal académico acerca de la suficiencia del equipo disponible, la accesibilidad y conectividad para llevar a cabo las actividades de aprendizaje, comunicación y atención tutorial y consultas, de acuerdo con los requerimientos de la modalidad en que se imparte la carrera.'],

                // ── Pauta 38 ─────────────────────────────────────────────────
                ['num' => 86, 'pauta' => 38, 'description' => 'Lista de acciones de capacitación en métodos y técnicas de enseñanza y evaluación, de acuerdo con la modalidad en que se imparte, en las que ha participado el personal académico en los últimos dos años.'],
                ['num' => 87, 'pauta' => 38, 'description' => 'Opinión del personal académico sobre la eficacia de las acciones de mejoramiento en métodos y técnicas de enseñanza y evaluación, de acuerdo con la modalidad en que se imparte, realizadas en los últimos dos años.'],

                // ── Pauta 39 ─────────────────────────────────────────────────
                ['num' => 88, 'pauta' => 39, 'description' => 'Diagnóstico de necesidades de actualización y desarrollo profesional del personal académico, administrativo, técnico y de apoyo.'],
                ['num' => 89, 'pauta' => 39, 'description' => 'Listado de las acciones de actualización impartidas al personal académico, administrativo, técnico y de apoyo en los últimos dos años.'],
                ['num' => 90, 'pauta' => 39, 'description' => 'Opinión del personal académico, administrativo, técnico y de apoyo sobre la calidad y pertinencia de las actividades de actualización facilitadas por la universidad o la carrera en las que ha participado en los últimos dos años.'],

                // ── Pauta 40 ─────────────────────────────────────────────────
                ['num' => 91, 'pauta' => 40, 'description' => 'Reglamento o normativa institucional para la evaluación del desempeño del personal académico y del personal administrativo, técnico y de apoyo.'],
                ['num' => 92, 'pauta' => 40, 'description' => 'Instrumentos para evaluar el desempeño del personal académico y del personal administrativo, técnico y de apoyo.'],
                ['num' => 93, 'pauta' => 40, 'description' => 'Descripción sobre el uso de los resultados de la evaluación del desempeño del personal académico y del personal administrativo, técnico y de apoyo.'],
                ['num' => 94, 'pauta' => 40, 'description' => 'Opinión de estudiantado en torno a la atención de sus valoraciones sobre el desempeño del personal académico.'],
                ['num' => 95, 'pauta' => 40, 'description' => 'Opinión del personal académico, administrativo, técnico y de apoyo sobre la eficacia del proceso de evaluación del desempeño y la realimentación recibida.'],

                // ── Pauta 41 ─────────────────────────────────────────────────
                ['num' => 96,  'pauta' => 41, 'description' => 'Lista de la infraestructura física y tecnológica disponible para el desarrollo de la carrera, de acuerdo con los requerimientos de la modalidad en que se imparte.'],
                ['num' => 97,  'pauta' => 41, 'description' => 'Opinión del personal académico, administrativo, técnico y de apoyo y estudiantado sobre la suficiencia de la infraestructura física y tecnológica para el quehacer académico de la carrera, de acuerdo con los requerimientos de la modalidad en que se imparte.'],

                // ── Pauta 42 ─────────────────────────────────────────────────
                ['num' => 98,  'pauta' => 42, 'description' => 'Normativa referida a higiene, seguridad y salud ocupacional, accesibilidad, prevención de riesgos y atención a desastres naturales.'],
                ['num' => 99,  'pauta' => 42, 'description' => 'Plan de atención a emergencias y desastres naturales.'],
                ['num' => 100, 'pauta' => 42, 'description' => 'Opinión del personal académico, administrativo, técnico y de apoyo, y estudiantado sobre la pertinencia de la normativa referida a higiene, seguridad, accesibilidad, salud ocupacional, prevención de riesgos y atención a los desastres naturales en la carrera.'],

                // ── Pauta 43 ─────────────────────────────────────────────────
                ['num' => 101, 'pauta' => 43, 'description' => 'Políticas sobre equidad de género.'],
                ['num' => 102, 'pauta' => 43, 'description' => 'Opinión del personal académico, administrativo, técnico y de apoyo, y estudiantado acerca de la pertinencia de las políticas y las acciones que realiza la carrera para asegurar la equidad de género.'],

                // ── Pauta 44 ─────────────────────────────────────────────────
                ['num' => 103, 'pauta' => 44, 'description' => 'Plan de mantenimiento y reemplazo de infraestructura y equipos.'],
                ['num' => 104, 'pauta' => 44, 'description' => 'Evidencia de las gestiones que ha realizado la carrera y sus resultados para el mantenimiento y reemplazo de infraestructura y equipos, así como la adquisición de nuevos recursos (en caso de requerirse).'],

                // ── Pauta 45 ─────────────────────────────────────────────────
                ['num' => 105, 'pauta' => 45, 'description' => 'Normativa para la prevención y atención del hostigamiento sexual.'],
                ['num' => 106, 'pauta' => 45, 'description' => 'Descripción de las funciones asignadas a las instancias encargadas de la protección y derechos ante situaciones de hostigamiento sexual.'],
                ['num' => 107, 'pauta' => 45, 'description' => 'Opinión del personal académico, administrativo, técnico y de apoyo, y estudiantado sobre la pertinencia y cumplimiento de la normativa de prevención y atención del hostigamiento sexual, así como sobre la acción de las instancias de protección.'],

                // ── Pauta 46 ─────────────────────────────────────────────────
                ['num' => 108, 'pauta' => 46, 'description' => 'Opinión del personal académico, administrativo, técnico y de apoyo acerca de la idoneidad de la persona que dirige la carrera.'],

                // ── Pauta 47 ─────────────────────────────────────────────────
                ['num' => 109, 'pauta' => 47, 'description' => 'Normativa relativa a la disponibilidad horaria y velocidad de respuesta de los servicios académicos y dependencias administrativas.'],
                ['num' => 110, 'pauta' => 47, 'description' => 'Opinión del estudiantado con respecto a la eficacia de los servicios administrativos y académicos para solventar sus necesidades.'],

                // ── Pauta 48 ─────────────────────────────────────────────────
                ['num' => 111, 'pauta' => 48, 'description' => 'Plan estratégico de la carrera.'],
                ['num' => 112, 'pauta' => 48, 'description' => 'Plan operativo anual vigente.'],

                // ── Pauta 49 ─────────────────────────────────────────────────
                ['num' => 113, 'pauta' => 49, 'description' => 'Protocolos atinentes a la gestión y soporte informático.'],
                ['num' => 114, 'pauta' => 49, 'description' => 'Organigrama institucional en el que conste la unidad de gestión y el soporte informático.'],
                ['num' => 115, 'pauta' => 49, 'description' => 'Certificación de la unidad de gestión y soporte informático por parte de la autoridad universitaria competente.'],
                ['num' => 116, 'pauta' => 49, 'description' => 'Opinión del personal académico, administrativo, técnico y de apoyo, y estudiantado en torno a la eficiencia del soporte informático que brinda la carrera.'],

                // ── Pauta 50 ─────────────────────────────────────────────────
                ['num' => 117, 'pauta' => 50, 'description' => 'Lineamientos y procedimientos (protocolos) para la sostenibilidad y operación de la integralidad de la infraestructura tecnológica, así como su mantenimiento y actualización.'],
                ['num' => 118, 'pauta' => 50, 'description' => 'Descripción de anchos de banda en torno a su capacidad para ofrecer conexión óptima en alta recurrencia y tráfico de información.'],
                ['num' => 119, 'pauta' => 50, 'description' => 'Descripción del software disponible en torno a su capacidad para proteger la intromisión de terceros.'],
                ['num' => 120, 'pauta' => 50, 'description' => 'Descripción de las características de los servidores y los procedimientos (protocolos) en torno a su capacidad para asegurar su redundancia y respaldo permanente y en casos de emergencia.'],
                ['num' => 121, 'pauta' => 50, 'description' => 'Opinión del personal académico y del estudiantado sobre la idoneidad del campus virtual para la implementación de la carrera.'],

                // ── Pauta 51 ─────────────────────────────────────────────────
                ['num' => 122, 'pauta' => 51, 'description' => 'Opinión del personal académico y del estudiantado acerca de la idoneidad del campus virtual para gestionar las actividades de aprendizaje no presenciales.'],

                // ── Pauta 52 ─────────────────────────────────────────────────
                ['num' => 123, 'pauta' => 52, 'description' => 'Tabla con indicación de cada sistema de información y su función.'],

                // ── Pauta 53 ─────────────────────────────────────────────────
                ['num' => 124, 'pauta' => 53, 'description' => 'Descripción del procedimiento de verificación y resguardo de la información.'],
                ['num' => 125, 'pauta' => 53, 'description' => 'Lista de medidas de seguridad en los títulos y certificaciones.'],

                // ── Pauta 54 ─────────────────────────────────────────────────
                ['num' => 126, 'pauta' => 54, 'description' => 'Descripción de los mecanismos que utiliza la carrera para la sostenibilidad de su calidad.'],

                // ── Pauta 55 ─────────────────────────────────────────────────
                ['num' => 127, 'pauta' => 55, 'description' => 'Lista de medios de comunicación mediante los cuales la carrera proporciona información relevante sobre su quehacer.'],
                ['num' => 128, 'pauta' => 55, 'description' => 'Opinión del personal académico, administrativo, técnico y de apoyo, y estudiantado acerca de la transparencia de la información proporcionada por la carrera.'],

                // ── Pauta 56 ─────────────────────────────────────────────────
                ['num' => 129, 'pauta' => 56, 'description' => 'Políticas de educación inclusiva.'],
                ['num' => 130, 'pauta' => 56, 'description' => 'Listado de recursos de apoyo educativo para atender a la diversidad del estudiantado.'],
                ['num' => 131, 'pauta' => 56, 'description' => 'Opinión del personal académico, administrativo, técnico y de apoyo, y estudiantado acerca de si la carrera sigue de forma eficaz los principios de inclusión.'],

                // ── Pauta 57 ─────────────────────────────────────────────────
                ['num' => 132, 'pauta' => 57, 'description' => 'Listado de acciones de vinculación con el sector social, público y productivo de los últimos dos años, con indicación del sector (gremio, empresa, grupo organizado, institución pública, etc.) y de los resultados obtenidos.'],

                // ── Pauta 58 ─────────────────────────────────────────────────
                ['num' => 133, 'pauta' => 58, 'description' => 'Listado de acciones relativas al logro de una gestión ambiental sostenible y sus resultados en los últimos dos años.'],
                ['num' => 134, 'pauta' => 58, 'description' => 'Opinión del personal académico, administrativo, técnico y de apoyo, y estudiantado sobre la eficacia de los esfuerzos de la carrera para incorporar la dimensión ambiental a su proyecto institucional, educativo y laboral.'],

                // ── Pauta 59 ─────────────────────────────────────────────────
                ['num' => 135, 'pauta' => 59, 'description' => 'Normativa aplicable a la carrera que le brinda posibilidades para realizar actividades de internacionalización.'],
                ['num' => 136, 'pauta' => 59, 'description' => 'Listado de actividades de internacionalización de los últimos dos años, tales como movilidad e intercambios académicos de personal académico y estudiantado, relaciones académicas de cooperación e intercambio internacional entre otras acciones realizadas en forma virtual y presencial.'],
                ['num' => 137, 'pauta' => 59, 'description' => 'Elementos del plan de estudios que evidencian una propuesta curricular hacia la formación para una ciudadanía global.'],
            ],
        ];
    }

    // -------------------------------------------------------------------------

    public function run(): void
    {
        $this->command->info('🌱 Iniciando FlexibleStructureSeeder (SINAES 2025)...');

        $data = $this->data();

        DB::table('MODELO_ESTRUCTURA')->insertOrIgnore([
            'nombre' => 'SINAES 2025 - Estructura Flexible',
            'tipo' => 'elemento_flexible',
            'descripcion' => 'Modelo flexible SINAES 2025: Dimensión > Pauta > Fuente.',
            'version' => '2025',
            'activo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $modelo = DB::table('MODELO_ESTRUCTURA')
            ->where('nombre', 'SINAES 2025 - Estructura Flexible')
            ->first();

        $mid = $modelo->modelo_estructura_id;

        if (DB::table('ELEMENTO')->where('modelo_estructura_id', $mid)->exists()) {
            $this->command->warn('  ℹ  ELEMENTO ya tiene datos para este modelo — omitiendo inserción.');

            return;
        }

        $insert = function (array $d) use ($mid): int {
            return DB::table('ELEMENTO')->insertGetId(array_merge([
                'modelo_estructura_id' => $mid,
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ], $d));
        };

        // -- Dimensiones ------------------------------------------------------
        $dimensionIds = [];
        foreach ($data['dimensions'] as $d) {
            $dimensionIds[$d['code']] = $insert([
                'padre_id' => null,
                'tipo' => 'dimension',
                'nombre' => $d['name'],
                'nomenclatura' => $d['code'],
                'descripcion' => $d['description'],
            ]);
        }
        $this->command->info('  ✓ '.count($data['dimensions']).' Dimensiones insertadas.');

        // -- Pautas -----------------------------------------------------------
        $pautaIds = [];
        foreach ($data['pautas'] as $p) {
            $dimensionCode = 'D'.$p['dimension'];
            $dimensionId = $dimensionIds[$dimensionCode] ?? null;

            $pautaIds[$p['num']] = $insert([
                'padre_id' => $dimensionId,
                'tipo' => 'pauta',
                'categoria' => $p['category'],
                'nombre' => 'Pauta '.$p['num'],
                'nomenclatura' => 'P'.$p['num'],
                'descripcion' => $p['description'],
            ]);
        }
        $this->command->info('  ✓ '.count($data['pautas']).' Pautas insertadas.');

        // -- Fuentes ----------------------------------------------------------
        foreach ($data['fuentes'] as $f) {
            $pautaId = $pautaIds[$f['pauta']] ?? null;

            $insert([
                'padre_id' => $pautaId,
                'tipo' => 'fuente',
                'nombre' => 'Fuente '.$f['num'],
                'nomenclatura' => 'F'.$f['num'],
                'descripcion' => $f['description'],
            ]);
        }
        $this->command->info('  ✓ '.count($data['fuentes']).' Fuentes insertadas.');

        $total = DB::table('ELEMENTO')->where('modelo_estructura_id', $mid)->count();
        $this->command->info("✅ FlexibleStructureSeeder completado — {$total} elementos creados.");
    }
}
