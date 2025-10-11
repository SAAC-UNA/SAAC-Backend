<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Services\UserAdminService;

class CreateTestUsersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:create-users';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Crea usuarios de prueba con permisos asignados';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $userAdmin = new UserAdminService();

        // Usuario 1: Con permisos de usuarios
        $user1 = User::find(1);
        if ($user1) {
            $userAdmin->setModulePermissions($user1, [
                'usuarios' => ['view', 'create', 'edit'],
                'evidencias' => ['view']
            ]);
            $this->info("Usuario {$user1->nombre} actualizado con permisos");
        }

        // Usuario 2: Con permisos de evidencias
        $user2 = User::find(2);
        if ($user2) {
            $userAdmin->setModulePermissions($user2, [
                'evidencias' => ['view', 'create', 'edit', 'delete'],
                'reportes' => ['generate']
            ]);
            $this->info("Usuario {$user2->nombre} actualizado con permisos");
        }

        // Usuario 3: Solo permisos básicos
        $user3 = User::find(3);
        if ($user3) {
            $userAdmin->setModulePermissions($user3, [
                'evidencias' => ['view']
            ]);
            $this->info("Usuario {$user3->nombre} actualizado con permisos");
        }

        $this->info('Usuarios de prueba creados exitosamente!');
    }
}
