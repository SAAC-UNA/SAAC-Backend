<?php

use Illuminate\Support\Facades\DB;

echo "=== PRUEBA 1: Ver Modelos Disponibles ===\n\n";
$modelos = DB::select('CALL SP_OBTENER_MODELOS_ESTRUCTURA()');
foreach ($modelos as $m) {
    echo "ID: {$m->modelo_estructura_id}\n";
    echo "Nombre: {$m->nombre}\n";
    echo "Tipo: {$m->tipo}\n";
    echo "Versión: {$m->version}\n";
    echo "Activo: {$m->activo}\n";
    echo str_repeat('-', 60) . "\n";
}

echo "\n=== PRUEBA 2: Ver Jerarquía Completa ===\n\n";
$jerarquia = DB::select('CALL SP_OBTENER_ARBOL_JERARQUIA(?)', [null]);
echo "Total elementos: " . count($jerarquia) . "\n\n";
foreach ($jerarquia as $j) {
    $indent = str_repeat('  ', $j->nivel);
    if ($j->tipo === 'dimension') {
        $icon = '📁';
    } elseif ($j->tipo === 'pauta') {
        $icon = '📋';
    } elseif ($j->tipo === 'fuente') {
        $icon = '📄';
    } else {
        $icon = '•';
    }
    echo "{$indent}{$icon} [{$j->jerarquia_id}] {$j->nombre} (Nivel: {$j->nivel})\n";
}

echo "\n=== PRUEBA 3: Filtrar solo PAUTAS ===\n\n";
$pautas = DB::select('CALL SP_OBTENER_JERARQUIAS(?)', ['pauta']);
echo "Total pautas: " . count($pautas) . "\n\n";
foreach ($pautas as $p) {
    echo "📋 [{$p->jerarquia_id}] {$p->nombre} - {$p->nomenclatura}\n";
}

echo "\n=== PRUEBA 4: Filtrar solo FUENTES ===\n\n";
$fuentes = DB::select('CALL SP_OBTENER_JERARQUIAS(?)', ['fuente']);
echo "Total fuentes: " . count($fuentes) . "\n\n";
foreach ($fuentes as $f) {
    echo "📄 [{$f->jerarquia_id}] {$f->nombre} - Padre: {$f->parent_id}\n";
}

echo "\n=== PRUEBA 5: Ver Procesos Existentes ===\n\n";
$procesos = DB::select('
    SELECT 
        p.proceso_id,
        p.tipo_proceso,
        p.modelo_estructura_id,
        me.nombre AS modelo_nombre,
        me.tipo AS modelo_tipo
    FROM PROCESO p
    LEFT JOIN MODELO_ESTRUCTURA me ON me.modelo_estructura_id = p.modelo_estructura_id
    ORDER BY p.proceso_id DESC
    LIMIT 5
');
echo "Últimos 5 procesos:\n\n";
foreach ($procesos as $proc) {
    echo "Proceso ID: {$proc->proceso_id}\n";
    echo "  Tipo: {$proc->tipo_proceso}\n";
    if ($proc->modelo_estructura_id) {
        echo "  Modelo: [{$proc->modelo_estructura_id}] {$proc->modelo_nombre}\n";
        echo "  Tipo Modelo: {$proc->modelo_tipo}\n";
    } else {
        echo "  Modelo: (Sin modelo asignado - proceso antiguo)\n";
    }
    echo str_repeat('-', 60) . "\n";
}

echo "\n✅ TODAS LAS PRUEBAS COMPLETADAS\n";
