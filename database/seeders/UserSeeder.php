<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Career;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    /**
     * Seeder robusto de Usuarios con Roles y Carreras
     * Campus Alajuela - Sede Regional Central Occidente
     */
    public function run(): void
    {
        $this->command->info('👥 Iniciando seeder de usuarios (Campus Alajuela)...');

        /**
         * PASO 1: Verificar que existan los roles necesarios
         */
        $this->command->info('🔐 Verificando roles...');
        
        $roles = [
            'Superusuario' => 'Acceso total al sistema',
            'Administrador' => 'Administrador de carrera',
            'Profesor' => 'Docente de la carrera',
            'Encargado de Acreditación' => 'Evaluador de evidencias',
        ];

        foreach ($roles as $roleName => $description) {
            Role::firstOrCreate(
                ['name' => $roleName, 'guard_name' => 'api']
            );
            $this->command->info("  ✅ Rol: {$roleName}");
        }

        /**
         * PASO 2: Obtener carreras del Campus Alajuela
         */
        $this->command->info('🎓 Obteniendo carreras...');
        
        $careerIngSistemas = Career::where('nombre', 'Ingeniería en Sistemas de Información')->first();
        $careerQuimica = Career::where('nombre', 'Química Industrial')->first();
        $careerAdministracion = Career::where('nombre', 'Administración de Empresas')->first();
        $careerIngles = Career::where('nombre', 'Inglés')->first();

        /**
         * PASO 3: Crear SuperUsuario del Sistema
         */
        $this->command->info('🦸 Creando SuperUsuario...');
        
        // Pablo Castillo - Funcionario TI
        $superUser = User::firstOrCreate(
            ['email' => 'pablo.castillo.quesada@una.cr'],
            [
                'cedula' => '203849675',
                'nombre' => 'Pablo Castillo Quesada',
                'status' => User::STATUS_ACTIVE,
            ]
        );
        $superUser->syncRoles(['Superusuario']);
        $this->command->info("  ✅ {$superUser->nombre} - Funcionario TI");

        /**
         * PASO 4: Crear usuarios por carrera
         */
        $users = [];
        
        // INGENIERÍA EN SISTEMAS
        if ($careerIngSistemas) {
            $this->command->info('💻 Usuarios de Ingeniería en Sistemas...');
            
            // Cristopher Montero - Administrador de Carrera de Sistemas
            $users[] = [
                'user_data' => [
                    'cedula' => '203948609',
                    'nombre' => 'Cristopher Montero Jimenez',
                    'email' => 'cristopher.montero.jimenez@una.cr',
                    'status' => User::STATUS_ACTIVE,
                ],
                'roles' => ['Administrador'],
                'careers' => [$careerIngSistemas->carrera_id],
            ];
            
            $users[] = [
                'user_data' => [
                    'cedula' => '102220222',
                    'nombre' => 'MSc. María González Vega',
                    'email' => 'maria.gonzalez@una.cr',
                    'status' => User::STATUS_ACTIVE,
                ],
                'roles' => ['Profesor', 'Encargado de Acreditación'],
                'careers' => [$careerIngSistemas->carrera_id],
            ];
            
            $users[] = [
                'user_data' => [
                    'cedula' => '103330333',
                    'nombre' => 'Ing. José Hernández Mora',
                    'email' => 'jose.hernandez@una.cr',
                    'status' => User::STATUS_ACTIVE,
                ],
                'roles' => ['Profesor'],
                'careers' => [$careerIngSistemas->carrera_id],
            ];
        }

        // QUÍMICA INDUSTRIAL
        if ($careerQuimica) {
            $this->command->info('🧪 Usuarios de Química Industrial...');
            
            // Alejandro Ugalde - Administrador de Carrera de Química
            $users[] = [
                'user_data' => [
                    'cedula' => '116540678',
                    'nombre' => 'Alejandro Ugalde Víquez',
                    'email' => 'alejandro.ugalde.viquez@una.cr',
                    'status' => User::STATUS_ACTIVE,
                ],
                'roles' => ['Administrador'],
                'careers' => [$careerQuimica->carrera_id],
            ];
            
            $users[] = [
                'user_data' => [
                    'cedula' => '202220222',
                    'nombre' => 'MSc. Patricia Rojas Quesada',
                    'email' => 'patricia.rojas@una.cr',
                    'status' => User::STATUS_ACTIVE,
                ],
                'roles' => ['Profesor', 'Encargado de Acreditación'],
                'careers' => [$careerQuimica->carrera_id],
            ];
        }

        // ADMINISTRACIÓN DE EMPRESAS
        if ($careerAdministracion) {
            $this->command->info('� Usuarios de Administración de Empresas...');
            
            $users[] = [
                'user_data' => [
                    'cedula' => '301110111',
                    'nombre' => 'MBA. Fernando Soto Méndez',
                    'email' => 'fernando.soto@una.cr',
                    'status' => User::STATUS_ACTIVE,
                ],
                'roles' => ['Administrador', 'Profesor'],
                'careers' => [$careerAdministracion->carrera_id],
            ];
            
            $users[] = [
                'user_data' => [
                    'cedula' => '302220222',
                    'nombre' => 'Lic. Laura Vindas Chacón',
                    'email' => 'laura.vindas@una.cr',
                    'status' => User::STATUS_ACTIVE,
                ],
                'roles' => ['Profesor'],
                'careers' => [$careerAdministracion->carrera_id],
            ];
        }

        // ENSEÑANZA DEL INGLÉS
        if ($careerIngles) {
            $this->command->info('�️  Usuarios de Enseñanza del Inglés...');
            
            $users[] = [
                'user_data' => [
                    'cedula' => '401110111',
                    'nombre' => 'MA. Roberto Smith Johnson',
                    'email' => 'roberto.smith@una.cr',
                    'status' => User::STATUS_ACTIVE,
                ],
                'roles' => ['Administrador', 'Profesor'],
                'careers' => [$careerIngles->carrera_id],
            ];
            
            $users[] = [
                'user_data' => [
                    'cedula' => '402220222',
                    'nombre' => 'BA. Gabriela Solís Núñez',
                    'email' => 'gabriela.solis@una.cr',
                    'status' => User::STATUS_ACTIVE,
                ],
                'roles' => ['Profesor', 'Encargado de Acreditación'],
                'careers' => [$careerIngles->carrera_id],
            ];
        }

        // Encargado de Acreditación general
        $this->command->info('🔍 Usuarios generales...');
        
        $users[] = [
            'user_data' => [
                'cedula' => '501110111',
                'nombre' => 'Dr. Ricardo Pérez Álvarez',
                'email' => 'ricardo.perez@una.cr',
                'status' => User::STATUS_ACTIVE,
            ],
            'roles' => ['Encargado de Acreditación'],
            'careers' => array_filter([
                $careerIngSistemas?->carrera_id,
                $careerQuimica?->carrera_id,
            ]),
        ];

        // Usuario inactivo para pruebas
        $users[] = [
            'user_data' => [
                'cedula' => '999990000',
                'nombre' => 'Usuario Inactivo Prueba',
                'email' => 'usuario.inactivo@una.cr',
                'status' => User::STATUS_INACTIVE,
            ],
            'roles' => ['Profesor'],
            'careers' => $careerIngSistemas ? [$careerIngSistemas->carrera_id] : [],
        ];

        /**
         * PASO 5: Guardar usuarios con roles y permisos
         */
        $this->command->info('💾 Guardando usuarios...');
        
        foreach ($users as $userData) {
            $user = User::firstOrCreate(
                ['email' => $userData['user_data']['email']],
                $userData['user_data']
            );

            // Asignar roles
            if (!empty($userData['roles'])) {
                $user->syncRoles($userData['roles']);
                
                // Asignar permisos directos basados en los roles para que el frontend los vea
                $permissions = [];
                foreach ($userData['roles'] as $roleName) {
                    $role = \Spatie\Permission\Models\Role::where('name', $roleName)
                        ->where('guard_name', 'api')
                        ->first();
                    if ($role) {
                        $permissions = array_merge($permissions, $role->permissions->pluck('name')->toArray());
                    }
                }
                
                // Sincronizar permisos directos (sin duplicados)
                if (!empty($permissions)) {
                    $user->syncPermissions(array_unique($permissions));
                }
            }

            // Asignar carreras
            if (!empty($userData['careers'])) {
                DB::table('CARRERA_USUARIO')
                    ->where('usuario_id', $user->usuario_id)
                    ->delete();

                foreach ($userData['careers'] as $careerId) {
                    DB::table('CARRERA_USUARIO')->insert([
                        'usuario_id' => $user->usuario_id,
                        'carrera_id' => $careerId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            $rolesStr = implode(', ', $userData['roles'] ?? []);
            $this->command->info("  ✅ {$user->nombre} ({$rolesStr})");
        }

        /**
         * PASO 6: Resumen
         */
        $this->command->info('');
        $this->command->info('📊 RESUMEN:');
        $this->command->info('  👥 Total usuarios: ' . User::count());
        $this->command->info('  ✅ Activos: ' . User::where('status', User::STATUS_ACTIVE)->count());
        $this->command->info('  ❌ Inactivos: ' . User::where('status', User::STATUS_INACTIVE)->count());
        $this->command->info('  🔗 Asignaciones: ' . DB::table('CARRERA_USUARIO')->count());
        $this->command->info('');
        $this->command->info('🎉 Usuarios creados exitosamente!');
    }
}
