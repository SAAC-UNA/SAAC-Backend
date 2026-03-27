<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AuthRevokeAllCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'auth:revoke-all';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Invalida todas las sesiones y tokens activos (database + Sanctum)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        DB::table('sessions')->truncate();
        DB::table('personal_access_tokens')->truncate();

        $this->info('Sesiones y tokens invalidados correctamente.');
        return self::SUCCESS;
    }
}
