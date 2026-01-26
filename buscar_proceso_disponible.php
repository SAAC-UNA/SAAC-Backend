<?php
// Script para buscar el primer proceso disponible sin compromiso de mejora

use Illuminate\Support\Facades\DB;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class);

$pid = DB::table('PROCESO as p')
    ->leftJoin('COMPROMISO_MEJORA as cm', 'cm.proceso_id', '=', 'p.proceso_id')
    ->whereNull('cm.compromiso_mejora_id')
    ->orderBy('p.proceso_id', 'asc')
    ->value('p.proceso_id');

if ($pid) {
    echo "Primer proceso disponible: $pid\n";
} else {
    echo "No hay procesos disponibles sin compromiso de mejora.\n";
}
