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
                'guard_name' => 'api'
            ]);
        }

        /**
         * Buscar carreras existentes
         */
        $careerIng = Career::where('nombre', '=', 'Ingeniería en Sistemas ')->first();
        $careerQuimi = Career::where('nombre', '=', 'Química')->first();

        if (!$careerIng || !$careerQuimi) {
            $this->command->error(' No se encontraron las carreras Ingeniería en Sistemas  o Química. Cárgalas primero desde Postman.');
            return;
        }

        /**
         * Crear usuarios base
         */
        // SuperUsuario
        $super = User::firstOrCreate(
            [
                'email' => 'pablo.castillo.quesada@una.cr',
                'cedula' => '999999999',
            ],
            [
                'nombre' => 'Pablo Castillo Quesada',
            ]
        );
        $super->assignRole('SuperUsuario');

        // Administrador Ingeniería en Sistemas 
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

        // Administrador Química
        $adminQuimi = User::firstOrCreate(
            [
                'email' => 'alejandro.ugalde.villalobos@est.una.ac.cr',
                'cedula' => '202038940',
            ],
            [
                'nombre' => 'Alejandro Ugalde Villalobos',
            ]
        );
        $adminQuimi->assignRole('Administrador');

        /**
         * Asignar carreras a los usuarios (tabla CARRERA_USUARIO)
         */
        DB::table('CARRERA_USUARIO')->whereIn('usuario_id', [
            $adminInge->usuario_id,
            $adminQuimi->usuario_id
        ])->delete();

        DB::table('CARRERA_USUARIO')->insert([
            [
                'usuario_id' => $adminInge->usuario_id,
                'carrera_id' => $careerIng->carrera_id,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'usuario_id' => $adminQuimi->usuario_id,
                'carrera_id' => $careerQuimi->carrera_id,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        /**
         * Confirmación final
         */
        $this->command->info(' Usuarios simulados creados y vinculados correctamente a sus carreras.');
    }
}
