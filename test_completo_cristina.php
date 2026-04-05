<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\CriterionApprovalService;
use App\Models\EvidenceApproval;
use App\Models\CriterionApproval;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

// ── Configuración ────────────────────────────────────────────────────────────
$CRITERIO_ID  = 3;
$PROCESO_ID   = 1;
$EVIDENCIA_ID = 5;          // evidencia 5 → Ana Cristina (ID:5)
$CRISTINA_ID  = 5;

$evaluador = User::find(1); // Naydelin
Auth::setUser($evaluador);

$service = new CriterionApprovalService();

$errores = [];

function ok(string $msg): void  { echo "  ✔ {$msg}\n"; }
function fail(string $msg, array &$e): void { echo "  ✘ {$msg}\n"; $e[] = $msg; }

function notifsCristina(int $desde, int $cristinaId): array
{
    return Notification::where('usuario_id', $cristinaId)
        ->where('notificacion_id', '>', $desde)
        ->orderBy('notificacion_id')
        ->get()
        ->toArray();
}

function maxNotifId(): int
{
    return (int) Notification::max('notificacion_id');
}

// ── Limpieza inicial ─────────────────────────────────────────────────────────
echo "\n=== LIMPIEZA INICIAL ===\n";
DB::table('APROBACION_EVIDENCIA')->where('evidencia_id', $EVIDENCIA_ID)->where('proceso_id', $PROCESO_ID)->delete();
DB::table('APROBACION_CRITERIO')->where('criterio_id', $CRITERIO_ID)->where('proceso_id', $PROCESO_ID)->delete();
Notification::where('usuario_id', $CRISTINA_ID)->delete();
echo "  Registros de aprobación y notificaciones de Cristina eliminados para empezar limpio.\n";

// ════════════════════════════════════════════════════════════════════════════
// PRUEBA 1 — rejectCriterion (bloque) con comentario
// Espera: email+app para Cristina, comentario propagado a APROBACION_EVIDENCIA
// ════════════════════════════════════════════════════════════════════════════
echo "\n=== PRUEBA 1: rejectCriterion (bloque con comentario) ===\n";
$antes = maxNotifId();

$result1 = $service->rejectCriterion(
    $CRITERIO_ID, $PROCESO_ID, $evaluador->usuario_id,
    'Falta firma en todos los documentos.',
    '2026-06-30'
);

// Verificar estado del bloque
$bloque = $result1['raiz'];
if ($bloque->estado === 'rechazado') ok("Bloque estado=rechazado");
else fail("Bloque debería ser rechazado, es: {$bloque->estado}", $errores);

// Verificar comentario en APROBACION_EVIDENCIA de evidencia 5
$evApproval = EvidenceApproval::where('evidencia_id', $EVIDENCIA_ID)->where('proceso_id', $PROCESO_ID)->first();
if ($evApproval && $evApproval->comentario === 'Falta firma en todos los documentos.') {
    ok("Comentario propagado a APROBACION_EVIDENCIA evidencia 5");
} else {
    fail("Comentario NO propagado. Valor: " . ($evApproval->comentario ?? 'null'), $errores);
}

// Verificar notificación de Cristina
$notifs1 = notifsCristina($antes, $CRISTINA_ID);
if (count($notifs1) >= 1) {
    $n = $notifs1[0];
    ok("Notificación creada para Ana Cristina (ID:{$n['notificacion_id']})");
    if ($n['canal'] === 'ambos') ok("Canal=ambos ✓");
    else fail("Canal debería ser ambos, es: {$n['canal']}", $errores);
    if ($n['estado_email'] === 'enviado') ok("Email enviado ✓");
    else fail("Email no enviado. Estado: {$n['estado_email']}", $errores);
    if ($n['tipo_evento'] === 'rechazo_evidencia') ok("Tipo=rechazo_evidencia ✓");
    else fail("Tipo incorrecto: {$n['tipo_evento']}", $errores);
    echo "  Título: {$n['titulo']}\n";
} else {
    fail("No se creó notificación para Ana Cristina", $errores);
}

// ════════════════════════════════════════════════════════════════════════════
// PRUEBA 2 — approveCriterion (bloque)
// Espera: notificación INTERNA (sin email) para Cristina
// ════════════════════════════════════════════════════════════════════════════
echo "\n=== PRUEBA 2: approveCriterion (bloque) ===\n";
$antes = maxNotifId();

$result2 = $service->approveCriterion(
    $CRITERIO_ID, $PROCESO_ID, $evaluador->usuario_id, null
);

$bloque2 = $result2['raiz'];
if ($bloque2->estado === 'aprobado') ok("Bloque estado=aprobado");
else fail("Bloque debería ser aprobado, es: {$bloque2->estado}", $errores);

$notifs2 = notifsCristina($antes, $CRISTINA_ID);
if (count($notifs2) >= 1) {
    $n = $notifs2[0];
    ok("Notificación creada para Ana Cristina (ID:{$n['notificacion_id']})");
    if ($n['canal'] === 'interno') ok("Canal=interno (aprobación no es crítica) ✓");
    else fail("Canal debería ser interno, es: {$n['canal']}", $errores);
    if ($n['estado_email'] === 'no_aplica') ok("estado_email=no_aplica (correcto, solo interno) ✓");
    else fail("estado_email debería ser no_aplica, es: {$n['estado_email']}", $errores);
    if ($n['tipo_evento'] === 'aprobacion_evidencia') ok("Tipo=aprobacion_evidencia ✓");
    else fail("Tipo incorrecto: {$n['tipo_evento']}", $errores);
    echo "  Título: {$n['titulo']}\n";
} else {
    fail("No se creó notificación para Ana Cristina", $errores);
}

// ════════════════════════════════════════════════════════════════════════════
// PRUEBA 3 — rejectIndividualEvidence (evidencia 5 de Cristina)
// Espera: email+app para Cristina, comentario en APROBACION_EVIDENCIA
// Nota: evidencia 5 estaba aprobada (prueba 2), bloque=aprobado → no bloqueada
// ════════════════════════════════════════════════════════════════════════════
echo "\n=== PRUEBA 3: rejectIndividualEvidence (evidencia 5, individual) ===\n";
// Limpiar notificaciones recientes de Cristina para evitar el filtro anti-duplicados (5 min)
Notification::where('usuario_id', $CRISTINA_ID)->delete();
$antes = maxNotifId();

$result3 = $service->rejectIndividualEvidence(
    $CRITERIO_ID, $EVIDENCIA_ID, $PROCESO_ID, $evaluador->usuario_id,
    'Documento incompleto, falta la página 3.',
    '2026-07-15'
);

$evApproval3 = EvidenceApproval::where('evidencia_id', $EVIDENCIA_ID)->where('proceso_id', $PROCESO_ID)->first();
if ($evApproval3 && $evApproval3->estado === 'rechazado') ok("APROBACION_EVIDENCIA estado=rechazado");
else fail("Estado incorrecto: " . ($evApproval3->estado ?? 'null'), $errores);
if ($evApproval3 && $evApproval3->comentario === 'Documento incompleto, falta la página 3.')  {
    ok("Comentario individual en APROBACION_EVIDENCIA ✓");
} else {
    fail("Comentario incorrecto: " . ($evApproval3->comentario ?? 'null'), $errores);
}

$notifs3 = notifsCristina($antes, $CRISTINA_ID);
if (count($notifs3) >= 1) {
    $n = $notifs3[0];
    ok("Notificación creada para Ana Cristina (ID:{$n['notificacion_id']})");
    if ($n['canal'] === 'ambos') ok("Canal=ambos ✓");
    else fail("Canal debería ser ambos, es: {$n['canal']}", $errores);
    if ($n['estado_email'] === 'enviado') ok("Email enviado ✓");
    else fail("Email no enviado. Estado: {$n['estado_email']}", $errores);
    echo "  Título: {$n['titulo']}\n";
} else {
    fail("No se creó notificación para Ana Cristina", $errores);
}

// ════════════════════════════════════════════════════════════════════════════
// PRUEBA 4 — approveIndividualEvidence (evidencia 5)
// Espera: notificación INTERNA (sin email)
// ════════════════════════════════════════════════════════════════════════════
echo "\n=== PRUEBA 4: approveIndividualEvidence (evidencia 5, individual) ===\n";
// Limpiar de nuevo para evitar duplicados
Notification::where('usuario_id', $CRISTINA_ID)->delete();
$antes = maxNotifId();

$result4 = $service->approveIndividualEvidence(
    $CRITERIO_ID, $EVIDENCIA_ID, $PROCESO_ID, $evaluador->usuario_id
);

$evApproval4 = EvidenceApproval::where('evidencia_id', $EVIDENCIA_ID)->where('proceso_id', $PROCESO_ID)->first();
if ($evApproval4 && $evApproval4->estado === 'aprobado') ok("APROBACION_EVIDENCIA estado=aprobado");
else fail("Estado incorrecto: " . ($evApproval4->estado ?? 'null'), $errores);

$notifs4 = notifsCristina($antes, $CRISTINA_ID);
if (count($notifs4) >= 1) {
    $n = $notifs4[0];
    ok("Notificación creada para Ana Cristina (ID:{$n['notificacion_id']})");
    if ($n['canal'] === 'interno') ok("Canal=interno (aprobación no crítica) ✓");
    else fail("Canal debería ser interno, es: {$n['canal']}", $errores);
    if ($n['estado_email'] === 'no_aplica') ok("estado_email=no_aplica ✓");
    else fail("estado_email debería ser no_aplica, es: {$n['estado_email']}", $errores);
    echo "  Título: {$n['titulo']}\n";
} else {
    fail("No se creó notificación para Ana Cristina", $errores);
}

// ════════════════════════════════════════════════════════════════════════════
// RESUMEN
// ════════════════════════════════════════════════════════════════════════════
echo "\n=== RESUMEN ===\n";
if (empty($errores)) {
    echo "  TODAS LAS PRUEBAS PASARON ✔\n\n";
} else {
    echo "  FALLARON " . count($errores) . " verificación(es):\n";
    foreach ($errores as $e) {
        echo "    - {$e}\n";
    }
    echo "\n";
}
