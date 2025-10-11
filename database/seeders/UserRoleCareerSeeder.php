<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Career;
use Spatie\Permission\Models\Role;

class UserRoleCareerSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info(' Iniciando seeder de usuarios, roles y relación con carreras...');

        /**
         * Crear roles principales (si no existen)
         */
        $roles = ['SuperUsuario', 'Administrador'];

        foreach ($roles as $role) {
            Role::firstOrCreate([
                'name' => $role,
                'guard_name' => 'web'
            ]);
        }

        /**
         * Buscar carreras existentes
         */
        $careerIng = Career::where('nombre', 'LIKE', '%Ingeniería%')->first();
        $careerEdu = Career::where('nombre', 'LIKE', '%Educación%')->first();

        if (!$careerIng || !$careerEdu) {
            $this->command->error(' No se encontraron las carreras Ingeniería o Educación. Cárgalas primero desde Postman.');
            return;
        }

        /**
         * Crear usuarios base
         */
        // SuperUsuario
        $super = User::firstOrCreate(
            [
                'email' => 'pablo.castillo.quesada@una.cr',
                'cedula' => '101010101',
            ],
            [
                'nombre' => 'Pablo Castillo Quesada',
            ]
        );
        $super->assignRole('SuperUsuario');

        // 🔹 Administrador Ingeniería
        $adminInge = User::firstOrCreate(
            [
                'email' => 'cristopher.montero.jimenez@una.ac.cr',
                'cedula' => '203948609',
            ],
            [
                'nombre' => 'Cristopher Montero Jimenez',
            ]
        );
        $adminInge->assignRole('Administrador');

        // 🔹 Administrador Educación
        $adminEdu = User::firstOrCreate(
            [
                'email' => 'ian.villegas.jimenez@est.una.ac.cr',
                'cedula' => '202038940',
            ],
            [
                'nombre' => 'Ian Villegas Jimenez',
            ]
        );
        $adminEdu->assignRole('Administrador');

        /**
         * Asignar carreras a los usuarios (tabla CARRERA_USUARIO)
         */
        DB::table('CARRERA_USUARIO')->whereIn('usuario_id', [
            $adminInge->usuario_id,
            $adminEdu->usuario_id
        ])->delete();

        DB::table('CARRERA_USUARIO')->insert([
            [
                'usuario_id' => $adminInge->usuario_id,
                'carrera_id' => $careerIng->carrera_id,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'usuario_id' => $adminEdu->usuario_id,
                'carrera_id' => $careerEdu->carrera_id,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        /**
         *Confirmación final
         */
        $this->command->info(' Usuarios simulados creados y vinculados correctamente a sus carreras.');
    }
}
