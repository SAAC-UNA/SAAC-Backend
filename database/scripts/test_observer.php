<?php
require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\ElementAssignment;
use App\Models\ExtensionRequest;

$eaId = 10;
$solicitudId = 4;

// Estado antes
$ea = ElementAssignment::find($eaId);
$sol = ExtensionRequest::find($solicitudId);

echo "=== ANTES ===\n";
echo "EA {$eaId} estado: {$ea->estado}\n";
echo "Solicitud {$solicitudId} estado: {$sol->estado}\n";
echo "Solicitud {$solicitudId} justificacion: " . ($sol->justificacion ?? 'null') . "\n\n";

// Disparar el observer
$ea->estado = ElementAssignment::ESTADO_COMPLETADO;
$ea->save();

// Estado después
$sol->refresh();
$ea->refresh();

echo "=== DESPUÉS ===\n";
echo "EA {$eaId} estado: {$ea->estado}\n";
echo "Solicitud {$solicitudId} estado: {$sol->estado}\n";
echo "Solicitud {$solicitudId} justificacion: " . ($sol->justificacion ?? 'null') . "\n\n";

if ($sol->estado === 'cancelada') {
    echo "✅ Observer funcionó correctamente.\n";
} else {
    echo "❌ Observer NO se disparó.\n";
}
