<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EvidenceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Pobla evidencias representativas del Modelo Evaluativo SINAES
     * Las evidencias están numeradas del 1 al 348 (enumeración única)
     * Muestra de algunas evidencias para pruebas
     */
    public function run(): void
    {
        // Obtener ID del estado de evidencia (asumiendo que existe un estado activo/pendiente)
        $estadoEvidencia = DB::table('ESTADO_EVIDENCIA')->first();
        
        if (!$estadoEvidencia) {
            $this->command->error('❌ No se encontró ningún estado de evidencia. Ejecuta EvidenceStateSeeder primero.');
            return;
        }

        // Obtener criterios
        $criterio1_1_1 = DB::table('CRITERIO')->where('nomenclatura', '1.1.1')->first();
        $criterio1_1_2 = DB::table('CRITERIO')->where('nomenclatura', '1.1.2')->first();
        $criterio1_2_1 = DB::table('CRITERIO')->where('nomenclatura', '1.2.1')->first();
        $criterio1_2_2 = DB::table('CRITERIO')->where('nomenclatura', '1.2.2')->first();
        $criterio2_1_1 = DB::table('CRITERIO')->where('nomenclatura', '2.1.1')->first();
        $criterio2_1_2 = DB::table('CRITERIO')->where('nomenclatura', '2.1.2')->first();

        $evidencias = [
            // Evidencias del criterio 1.1.1 (Información y promoción)
            [
                'criterio_id' => $criterio1_1_1->criterio_id,
                'estado_evidencia_id' => $estadoEvidencia->estado_evidencia_id,
                'descripcion' => 'Lista descriptiva de los materiales informativos disponibles',
                'nomenclatura' => '20',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'criterio_id' => $criterio1_1_1->criterio_id,
                'estado_evidencia_id' => $estadoEvidencia->estado_evidencia_id,
                'descripcion' => 'Descripción de la estrategia de comunicación y divulgación',
                'nomenclatura' => '21',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // Evidencias del criterio 1.1.2
            [
                'criterio_id' => $criterio1_1_2->criterio_id,
                'estado_evidencia_id' => $estadoEvidencia->estado_evidencia_id,
                'descripcion' => 'Porcentaje de estudiantes que reciben información requerida',
                'nomenclatura' => '22',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'criterio_id' => $criterio1_1_2->criterio_id,
                'estado_evidencia_id' => $estadoEvidencia->estado_evidencia_id,
                'descripcion' => 'Porcentaje de estudiantes que opina sobre entrega oportuna',
                'nomenclatura' => '23',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // Evidencias del criterio 1.2.1 (Proceso de admisión)
            [
                'criterio_id' => $criterio1_2_1->criterio_id,
                'estado_evidencia_id' => $estadoEvidencia->estado_evidencia_id,
                'descripcion' => 'Normativa y lista de trámites y requisitos de ingreso',
                'nomenclatura' => '24',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'criterio_id' => $criterio1_2_1->criterio_id,
                'estado_evidencia_id' => $estadoEvidencia->estado_evidencia_id,
                'descripcion' => 'Medios de difusión de trámites y requisitos de ingreso',
                'nomenclatura' => '25',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // Evidencias del criterio 1.2.2 (Igualdad de oportunidades)
            [
                'criterio_id' => $criterio1_2_2->criterio_id,
                'estado_evidencia_id' => $estadoEvidencia->estado_evidencia_id,
                'descripcion' => 'Descripción de políticas que garantizan igualdad de oportunidades',
                'nomenclatura' => '26',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'criterio_id' => $criterio1_2_2->criterio_id,
                'estado_evidencia_id' => $estadoEvidencia->estado_evidencia_id,
                'descripcion' => 'Distribución de estudiantes admitidos por sexo y nacionalidad',
                'nomenclatura' => '27',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'criterio_id' => $criterio1_2_2->criterio_id,
                'estado_evidencia_id' => $estadoEvidencia->estado_evidencia_id,
                'descripcion' => 'Descripción de condiciones para personas con discapacidad',
                'nomenclatura' => '28',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // Evidencias del criterio 2.1.1 (Plan de estudios)
            [
                'criterio_id' => $criterio2_1_1->criterio_id,
                'estado_evidencia_id' => $estadoEvidencia->estado_evidencia_id,
                'descripcion' => 'Documento oficial con antecedentes y fundamentos conceptuales',
                'nomenclatura' => '40',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'criterio_id' => $criterio2_1_1->criterio_id,
                'estado_evidencia_id' => $estadoEvidencia->estado_evidencia_id,
                'descripcion' => 'Descripción de medios de divulgación del documento',
                'nomenclatura' => '41',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // Evidencias del criterio 2.1.2
            [
                'criterio_id' => $criterio2_1_2->criterio_id,
                'estado_evidencia_id' => $estadoEvidencia->estado_evidencia_id,
                'descripcion' => 'Justificación de congruencia de fines con postulados institucionales',
                'nomenclatura' => '42',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'criterio_id' => $criterio2_1_2->criterio_id,
                'estado_evidencia_id' => $estadoEvidencia->estado_evidencia_id,
                'descripcion' => 'Porcentaje del personal que considera que fines guían el proceso',
                'nomenclatura' => '43',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('EVIDENCIA')->insert($evidencias);

        $this->command->info('✅ 13 evidencias SINAES creadas exitosamente (muestra representativa: #20-28, #40-43)');
    }
}
