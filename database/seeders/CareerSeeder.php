<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Career;
use App\Models\Faculty;

class CareerSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🎓 Creando Carreras del Campus Alajuela...');

        $facultadExactas = Faculty::where('nombre', 'LIKE', '%Ciencias Exactas%')->first();
        $facultadSociales = Faculty::where('nombre', 'LIKE', '%Ciencias Sociales%')->first();
        $facultadLetras = Faculty::where('nombre', 'LIKE', '%Filosofía y Letras%')->first();
        
        if (!$facultadExactas) {
            $this->command->error('❌ Facultad de Ciencias Exactas y Naturales no encontrada.');
            return;
        }

        $careers = [];

        // INGENIERÍA EN SISTEMAS DE INFORMACIÓN
        if ($facultadExactas) {
            $careers[] = [
                'facultad_id' => $facultadExactas->facultad_id,
                'nombre' => 'Ingeniería en Sistemas de Información',
                'activo' => true,
            ];

            // QUÍMICA INDUSTRIAL
            $careers[] = [
                'facultad_id' => $facultadExactas->facultad_id,
                'nombre' => 'Química Industrial',
                'activo' => true,
            ];
        }

        // ADMINISTRACIÓN DE EMPRESAS
        if ($facultadSociales) {
            $careers[] = [
                'facultad_id' => $facultadSociales->facultad_id,
                'nombre' => 'Administración de Empresas',
                'activo' => true,
            ];
        }

        // INGLÉS
        if ($facultadLetras) {
            $careers[] = [
                'facultad_id' => $facultadLetras->facultad_id,
                'nombre' => 'Inglés',
                'activo' => true,
            ];
        }

        foreach ($careers as $career) {
            if (isset($career['facultad_id'])) {
                $created = Career::firstOrCreate(
                    ['nombre' => $career['nombre'], 'facultad_id' => $career['facultad_id']],
                    $career
                );
                $this->command->info("✅ {$created->nombre}");
            }
        }

        $this->command->info('🎉 Carreras creadas');
    }
}
