<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // 🔹 Superusuario
        User::firstOrCreate(
            ['email' => 'admin@saacuna.local'],
            [
                'cedula' => '101010101',
                'nombre' => 'Administrador General',
                'status' => 'active',
            ]
        );

        // 🔹 Usuario encargado de evidencias
        User::firstOrCreate(
            ['email' => 'evidencias@saacuna.local'],
            [
                'cedula' => '202020202',
                'nombre' => 'Encargado de Evidencias',
                'status' => 'active',
            ]
        );

        // 🔹 Usuario encargado de ciclos
        User::firstOrCreate(
            ['email' => 'ciclos@saacuna.local'],
            [
                'cedula' => '303030303',
                'nombre' => 'Encargado de Ciclos',
                'status' => 'inactive',
            ]
        );
    }
}
