<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CriterionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Pobla criterios representativos del Modelo Evaluativo SINAES
     * Muestra de 2-3 criterios por componente para pruebas
     */
    public function run(): void
    {
        // Obtener componentes
        $comp1_1 = DB::table('COMPONENTE')->where('nomenclatura', '1.1')->first();
        $comp1_2 = DB::table('COMPONENTE')->where('nomenclatura', '1.2')->first();
        $comp2_1 = DB::table('COMPONENTE')->where('nomenclatura', '2.1')->first();
        $comp2_2 = DB::table('COMPONENTE')->where('nomenclatura', '2.2')->first();
        $comp3_1 = DB::table('COMPONENTE')->where('nomenclatura', '3.1')->first();
        $comp4_1 = DB::table('COMPONENTE')->where('nomenclatura', '4.1')->first();

        $criterios = [
            // Componente 1.1 - Información y promoción
            [
                'componente_id' => $comp1_1->componente_id,
                'descripcion' => 'Debe contarse con medios que permitan acceso público a información sobre la carrera, los trámites de ingreso, la duración de los estudios, los requisitos y procedimientos para las convalidaciones y reconocimientos y las tarifas de los trámites académico-administrativos.',
                'nomenclatura' => '1.1.1',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'componente_id' => $comp1_1->componente_id,
                'descripcion' => 'El estudiante debe ser informado oportunamente y de forma veraz, al menos sobre el plan de estudios, tiempo promedio de graduación, costos, normativa, fechas, trámites y servicios.',
                'nomenclatura' => '1.1.2',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // Componente 1.2 - Proceso de admisión e ingreso
            [
                'componente_id' => $comp1_2->componente_id,
                'descripcion' => 'Los trámites y requisitos de ingreso en la carrera deben estar claramente estipulados en una normativa y ser ampliamente difundidos.',
                'nomenclatura' => '1.2.1',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'componente_id' => $comp1_2->componente_id,
                'descripcion' => 'Debe promoverse el acceso a la carrera o programa en igualdad de oportunidades, sin discriminación y con respeto por la diversidad.',
                'nomenclatura' => '1.2.2',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // Componente 2.1 - Plan de estudios
            [
                'componente_id' => $comp2_1->componente_id,
                'descripcion' => 'La carrera debe contar con un documento descriptivo que contenga lo siguiente: antecedentes, fundamentos conceptuales, objetivos, fines, ejes curriculares y orientación metodológica.',
                'nomenclatura' => '2.1.1',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'componente_id' => $comp2_1->componente_id,
                'descripcion' => 'Los fines y objetivos de la carrera deben ser claros y congruentes con los postulados de la institución y guiar adecuadamente el proceso educativo.',
                'nomenclatura' => '2.1.2',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // Componente 2.2 - Personal académico
            [
                'componente_id' => $comp2_2->componente_id,
                'descripcion' => 'Se debe contar con normativas para el personal académico, que regule sus deberes y derechos en el ejercicio de la actividad docente.',
                'nomenclatura' => '2.2.1',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'componente_id' => $comp2_2->componente_id,
                'descripcion' => 'La carrera debe garantizar que su personal académico pueda participar en actividades de docencia, investigación y extensión social.',
                'nomenclatura' => '2.2.3',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // Componente 3.1 - Desarrollo docente
            [
                'componente_id' => $comp3_1->componente_id,
                'descripcion' => 'La dirección y el personal académico deben participar en la definición de las modificaciones del plan de estudios.',
                'nomenclatura' => '3.1.1',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'componente_id' => $comp3_1->componente_id,
                'descripcion' => 'El personal académico debe asistir a las diferentes actividades de coordinación que implica la gestión y desarrollo de la carrera, e involucrarse en ellas.',
                'nomenclatura' => '3.1.2',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // Componente 4.1 - Desempeño estudiantil
            [
                'componente_id' => $comp4_1->componente_id,
                'descripcion' => 'Debe existir un reglamento de evaluación de los aprendizajes que estipule aspectos como la escala de calificación, las normas de evaluación, los mecanismos y los plazos de apelación.',
                'nomenclatura' => '4.1.1',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'componente_id' => $comp4_1->componente_id,
                'descripcion' => 'La Universidad en general y la carrera en particular deben contar con mecanismos y recursos para registrar y ofrecer estadísticas e información anual del estudiantado.',
                'nomenclatura' => '4.1.2',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('CRITERIO')->insert($criterios);

        $this->command->info('✅ 12 criterios SINAES creados exitosamente (muestra representativa)');
    }
}
