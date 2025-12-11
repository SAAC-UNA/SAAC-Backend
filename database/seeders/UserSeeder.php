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
     * Seeder de Usuarios del LDAP
     * Usuarios importados desde users.ldif
     */
    public function run(): void
    {
        $this->command->info('👥 Iniciando seeder de usuarios del LDAP...');

        /**
         * PASO 1: Verificar que existan los roles necesarios
         */
        $this->command->info('🔐 Verificando roles...');
        
        $roles = [
            'Superusuario' => 'Acceso total al sistema',
            'Administrador' => 'Administrador de carrera',
            'Profesor' => 'Docente de la carrera',
        ];

        foreach ($roles as $roleName => $description) {
            Role::firstOrCreate(
                ['name' => $roleName, 'guard_name' => 'api']
            );
            $this->command->info("  ✅ Rol: {$roleName}");
        }

        /**
         * PASO 2: Obtener la carrera de Ingeniería en Sistemas
         */
        $this->command->info('🎓 Obteniendo carrera...');
        
        $careerIngSistemas = Career::where('nombre', 'Ingeniería en Sistemas de Información')->first();

        if (!$careerIngSistemas) {
            $this->command->warn('⚠️  No se encontró la carrera de Ingeniería en Sistemas.');
            $this->command->info('Los usuarios se crearán sin asignación de carrera.');
        } else {
            $this->command->info("  ✅ Carrera encontrada: {$careerIngSistemas->nombre}");
        }

        /**
         * PASO 3: Crear SuperUsuario del Sistema (Pablo Castillo)
         * dn: uid=203849675,ou=profesores,ou=users,dc=una,dc=local
         */
        $this->command->info('🦸 Creando SuperUsuario...');
        
        $superUser = User::firstOrCreate(
            ['email' => 'pablo.castillo.quesada@una.cr'],
            [
                'cedula' => '203849675',
                'nombre' => 'Pablo Castillo Quesada',
                'password' => null, // Usuario LDAP
                'status' => User::STATUS_ACTIVE,
            ]
        );
        $superUser->syncRoles(['Superusuario']);
        $this->command->info("  ✅ {$superUser->nombre} - Superusuario");

        /**
         * PASO 4: Crear usuarios profesores y estudiantes del LDAP
         */
        $users = [];

        // PROFESORES (ou=profesores,ou=users,dc=una,dc=local)
        $this->command->info('👨‍🏫 Profesores...');

        // Cristopher Montero Jimenez - Administrador
        // dn: uid=203948609,ou=profesores,ou=users,dc=una,dc=local
        $users[] = [
            'user_data' => [
                'cedula' => '203948609',
                'nombre' => 'Cristopher Montero Jimenez',
                'email' => 'cristopher.montero.jimenez@una.cr',
                'password' => null, // Usuario LDAP
                'status' => User::STATUS_ACTIVE,
            ],
            'roles' => ['Administrador'],
            'careers' => $careerIngSistemas ? [$careerIngSistemas->carrera_id] : [],
        ];

        // ESTUDIANTES DEL PROYECTO (ou=estudiantes,ou=users,dc=una,dc=local)
        $this->command->info('🎓 Estudiantes del proyecto...');

        // Naydelin Nayeli Jiron Castellon
        // dn: uid=801490957,ou=estudiantes,ou=users,dc=una,dc=local
        $users[] = [
            'user_data' => [
                'cedula' => '801490957',
                'nombre' => 'Naydelin Nayeli Jiron Castellon',
                'email' => 'nayidelin.jiron.castellon@est.una.ac.cr',
                'password' => null, // Usuario LDAP
                'status' => User::STATUS_ACTIVE,
            ],
            'roles' => ['Profesor'],
            'careers' => $careerIngSistemas ? [$careerIngSistemas->carrera_id] : [],
        ];

        // Jose Andres Jara Arias
        // dn: uid=208330811,ou=estudiantes,ou=users,dc=una,dc=local
        $users[] = [
            'user_data' => [
                'cedula' => '208330811',
                'nombre' => 'Jose Andres Jara Arias',
                'email' => 'jose.jara.arias@est.una.ac.cr',
                'password' => null, // Usuario LDAP
                'status' => User::STATUS_ACTIVE,
            ],
            'roles' => ['Profesor'],
            'careers' => $careerIngSistemas ? [$careerIngSistemas->carrera_id] : [],
        ];

        // Marisol Hidalgo Murillo
        // dn: uid=118620669,ou=estudiantes,ou=users,dc=una,dc=local
        $users[] = [
            'user_data' => [
                'cedula' => '118620669',
                'nombre' => 'Marisol Hidalgo Murillo',
                'email' => 'marisol.hidalgo.murillo@est.una.ac.cr',
                'password' => null, // Usuario LDAP
                'status' => User::STATUS_ACTIVE,
            ],
            'roles' => ['Profesor'],
            'careers' => $careerIngSistemas ? [$careerIngSistemas->carrera_id] : [],
        ];

        // Ian Enmanuel Villegas Jimenez
        // dn: uid=207800171,ou=estudiantes,ou=users,dc=una,dc=local
        $users[] = [
            'user_data' => [
                'cedula' => '207800171',
                'nombre' => 'Ian Enmanuel Villegas Jimenez',
                'email' => 'ian.villegas.jimenez@est.una.ac.cr',
                'password' => null, // Usuario LDAP
                'status' => User::STATUS_ACTIVE,
            ],
            'roles' => ['Profesor'],
            'careers' => $careerIngSistemas ? [$careerIngSistemas->carrera_id] : [],
        ];

        // Ana Cristina Zuniga Cardenas
        // dn: uid=206870079,ou=estudiantes,ou=users,dc=una,dc=local
        $users[] = [
            'user_data' => [
                'cedula' => '206870079',
                'nombre' => 'Ana Cristina Zuniga Cardenas',
                'email' => 'ana.zuniga.cardenas@est.una.ac.cr',
                'password' => null, // Usuario LDAP
                'status' => User::STATUS_ACTIVE,
            ],
            'roles' => ['Profesor'],
            'careers' => $careerIngSistemas ? [$careerIngSistemas->carrera_id] : [],
        ];

        /**
         * PASO 5: Guardar usuarios con roles y carreras
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
            }

            // Asignar carreras (relación CARRERA_USUARIO)
            if (!empty($userData['careers'])) {
                foreach ($userData['careers'] as $careerId) {
                    DB::table('CARRERA_USUARIO')->updateOrInsert(
                        [
                            'usuario_id' => $user->usuario_id,
                            'carrera_id' => $careerId,
                        ],
                        [
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]
                    );
                }
            }

            $rolesText = implode(', ', $userData['roles']);
            $this->command->info("  ✅ {$user->nombre} - {$rolesText}");
        }

        /**
         * PASO 6: Resumen
         */
        $totalUsers = User::count();
        $activeUsers = User::where('status', User::STATUS_ACTIVE)->count();

        $this->command->info('');
        $this->command->info('📊 Resumen:');
        $this->command->info("  Total usuarios: {$totalUsers}");
        $this->command->info("  Usuarios activos: {$activeUsers}");
        $this->command->info('');
        $this->command->info('✅ UserSeeder ejecutado exitosamente');
    }
}
