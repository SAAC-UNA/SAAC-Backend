<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Career;

class CareerSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🎓 Creando Carreras...');

        $careers = [
            [
                'nombre' => 'Ingeniería en Sistemas de Información',
                'activo' => true,
            ],
            [
                'nombre' => 'Química Industrial',
                'activo' => true,
            ],
            [
                'nombre' => 'Administración de Empresas',
                'activo' => true,
            ],
            [
                'nombre' => 'Inglés',
                'activo' => true,
            ],
        ];

        foreach ($careers as $career) {
            $created = Career::firstOrCreate(
                ['nombre' => $career['nombre']],
                $career
            );
            $this->command->info("✅ {$created->nombre}");
        }

        $this->command->info('🎉 Carreras creadas');
    }
}
