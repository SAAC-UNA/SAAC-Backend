<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ListProcesosDisponibles extends Command
{
    protected $signature = 'proceso:disponibles';
    protected $description = 'Lista los procesos sin compromiso de mejora asociado';

    public function handle()
    {
        $procesos = DB::table('PROCESO as p')
            ->leftJoin('COMPROMISO_MEJORA as cm', 'cm.proceso_id', '=', 'p.proceso_id')
            ->whereNull('cm.compromiso_mejora_id')
            ->orderBy('p.proceso_id', 'asc')
            ->select('p.proceso_id', 'p.ciclo_acreditacion_id')
            ->get();

        if ($procesos->isEmpty()) {
            $this->info('No hay procesos disponibles sin compromiso de mejora.');
        } else {
            $this->info('Procesos disponibles:');
            foreach ($procesos as $proceso) {
                $this->line('proceso_id: ' . $proceso->proceso_id . ' | ciclo_acreditacion_id: ' . $proceso->ciclo_acreditacion_id);
            }
        }
    }
}
