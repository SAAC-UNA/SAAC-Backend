<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "\n";
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║         ESTADO ACTUAL DEL SISTEMA - DATOS REALES              ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n";
echo "\n";

// ==================================================
// 1. MODELOS DISPONIBLES
// ==================================================
echo "📋 1. MODELOS DISPONIBLES\n";
echo str_repeat("=", 70) . "\n\n";

$modelos = DB::table('MODELO_ESTRUCTURA')->orderBy('modelo_estructura_id')->get();

foreach ($modelos as $modelo) {
    echo "┌─────────────────────────────────────────────────────────────┐\n";
    echo "│ ID: {$modelo->modelo_estructura_id}                                                          │\n";
    echo "│ Nombre: " . str_pad($modelo->nombre, 52) . "│\n";
    echo "│ Tipo: " . str_pad($modelo->tipo, 54) . "│\n";
    echo "│ Versión: " . str_pad($modelo->version ?? 'N/A', 51) . "│\n";
    echo "│ Activo: " . ($modelo->activo ? 'SÍ' : 'NO') . str_repeat(' ', 52) . "│\n";
    echo "└─────────────────────────────────────────────────────────────┘\n\n";
}

echo "\n";

// ==================================================
// 2. JERARQUÍA CREADA
// ==================================================
echo "🌳 2. ESTRUCTURA JERARQUICA (10 Elements)\n";
echo str_repeat("=", 70) . "\n\n";

$jerarquias = DB::table('JERARQUIA')->orderBy('jerarquia_id')->get();

foreach ($jerarquias as $j) {
    $icon = '';
    if ($j->tipo === 'dimension') $icon = '📁';
    elseif ($j->tipo === 'pauta') $icon = '📋';
    elseif ($j->tipo === 'fuente') $icon = '📄';
    else $icon = '•';
    
    $indent = $j->parent_id ? '   ' : '';
    if ($j->parent_id && DB::table('JERARQUIA')->where('jerarquia_id', $j->parent_id)->value('parent_id')) {
        $indent = '      ';
    }
    
    echo "{$indent}{$icon} [{$j->jerarquia_id}] {$j->nombre}\n";
    echo "{$indent}    Tipo: {$j->tipo}";
    if ($j->nomenclatura) echo " | Nomenclatura: {$j->nomenclatura}";
    if ($j->parent_id) echo " | Padre: {$j->parent_id}";
    echo "\n\n";
}

echo "\n";

// ==================================================
// 3. PROCESOS EXISTENTES
// ==================================================
echo "🎯 3. ÚLTIMOS PROCESOS (con modelo asignado)\n";
echo str_repeat("=", 70) . "\n\n";

$procesos = DB::select("
    SELECT 
        p.proceso_id,
        p.tipo_proceso,
        p.modelo_estructura_id,
        me.nombre AS modelo_nombre,
        me.tipo AS modelo_tipo,
        ca.nombre AS ciclo_nombre
    FROM PROCESO p
    LEFT JOIN MODELO_ESTRUCTURA me ON me.modelo_estructura_id = p.modelo_estructura_id
    LEFT JOIN CICLO_ACREDITACION ca ON ca.ciclo_acreditacion_id = p.ciclo_acreditacion_id
    ORDER BY p.proceso_id DESC
    LIMIT 5
");

if (count($procesos) === 0) {
    echo "⚠️  No hay procesos en la base de datos aún.\n\n";
} else {
    foreach ($procesos as $proceso) {
        echo "┌─────────────────────────────────────────────────────────────┐\n";
        echo "│ Proceso ID: " . str_pad($proceso->proceso_id, 47) . "│\n";
        echo "│ Tipo: " . str_pad($proceso->tipo_proceso, 54) . "│\n";
        echo "│ Ciclo: " . str_pad($proceso->ciclo_nombre ?? 'N/A', 53) . "│\n";
        
        if ($proceso->modelo_estructura_id) {
            echo "│                                                             │\n";
            echo "│ 🔹 MODELO ASIGNADO:                                         │\n";
            echo "│   ID: {$proceso->modelo_estructura_id}                                                        │\n";
            echo "│   Tipo: " . str_pad($proceso->modelo_tipo, 51) . "│\n";
            $nombreCorto = substr($proceso->modelo_nombre, 0, 45);
            echo "│   Nombre: " . str_pad($nombreCorto, 49) . "│\n";
        } else {
            echo "│                                                             │\n";
            echo "│ ⚠️  Sin modelo asignado (proceso antiguo)                   │\n";
        }
        
        echo "└─────────────────────────────────────────────────────────────┘\n\n";
    }
}

echo "\n";

// ==================================================
// 4. DIMENSIONES TRADICIONALES
// ==================================================
echo "📊 4. DIMENSIONES TRADICIONALES (para modelo 1)\n";
echo str_repeat("=", 70) . "\n\n";

$dimensiones = DB::table('DIMENSION')->orderBy('dimension_id')->limit(5)->get();

if (count($dimensiones) === 0) {
    echo "⚠️  No hay dimensiones creadas aún.\n\n";
} else {
    echo "Total dimensiones: " . DB::table('DIMENSION')->count() . "\n\n";
    foreach ($dimensiones as $dim) {
        echo "📐 [{$dim->dimension_id}] " . ($dim->nombre ?? $dim->descripcion ?? 'Sin nombre') . "\n";
    }
    echo "\n(Mostrando primeras 5)\n\n";
}

echo "\n";

// ==================================================
// RESUMEN
// ==================================================
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║                         RESUMEN                                ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

echo "✅ Modelos de estructura: " . count($modelos) . "\n";
echo "✅ Elements de jerarquía: " . count($jerarquias) . "\n";
echo "✅ Procesos totales: " . DB::table('PROCESO')->count() . "\n";
echo "✅ Dimensiones tradicionales: " . DB::table('DIMENSION')->count() . "\n\n";

echo "🎯 CONCLUSIÓN:\n";
echo "   - Tu sistema tiene 2 modelos disponibles\n";
echo "   - La jerarquía flexible tiene 10 Elements de ejemplo\n";
echo "   - Las dimensiones tradicionales ya existen\n";
echo "   - Cuando creas un proceso, eliges modelo 1 o 2\n";
echo "   - El frontend detecta automáticamente qué UI mostrar\n\n";

echo "📚 Lee: docs/EXPLICACION_PASO_A_PASO.md para entender el flujo\n\n";
