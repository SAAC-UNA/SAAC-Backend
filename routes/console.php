<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/**
 * HU-018: Programar verificación de plazos
 * 
 * Ejecutar diariamente a las 8:00 AM para notificar sobre:
 * - Plazos próximos a vencer (3, 7, 15 días)
 * - Plazos vencidos (fecha_limite < hoy)
 */
Schedule::command('notifications:check-deadlines')
    ->dailyAt('08:00')
    ->timezone('America/Costa_Rica')
    ->description('Verificar plazos de evidencias (próximos y vencidos)');
