<?php
require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$rows = DB::select("
    SELECT ea.elemento_asignacion_id, ea.estado, ea.fecha_limite,
           COUNT(sa.solicitud_ampliacion_id) as pendientes
    FROM ELEMENTO_ASIGNACION ea
    LEFT JOIN SOLICITUD_AMPLIACION sa
        ON sa.elemento_asignacion_id = ea.elemento_asignacion_id
        AND sa.estado = 'pendiente'
    WHERE ea.usuario_id = 3
    GROUP BY ea.elemento_asignacion_id, ea.estado, ea.fecha_limite
    ORDER BY ea.elemento_asignacion_id
");

echo str_pad('EA_ID', 8) . str_pad('ESTADO', 15) . str_pad('FECHA_LIMITE', 14) . "PENDIENTES\n";
echo str_repeat('-', 50) . "\n";
foreach ($rows as $r) {
    $usable = ($r->pendientes == 0 && in_array($r->estado, ['Pendiente', 'En Progreso'])) ? ' <-- OK' : '';
    echo str_pad($r->elemento_asignacion_id, 8)
       . str_pad($r->estado, 15)
       . str_pad($r->fecha_limite ?? 'null', 14)
       . $r->pendientes . $usable . "\n";
}
