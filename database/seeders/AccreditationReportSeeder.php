<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Models\AccreditationReport;

/**
 * AccreditationReportSeeder — No crea datos de demostración.
 *
 * Este seeder no crea informes de acreditación falsos. Los informes deben
 * subirse manualmente a través de la interfaz de administración.
 *
 * Solo limpia informes marcados con [DEMO] de ejecuciones anteriores.
 */
class AccreditationReportSeeder extends Seeder
{
    private const TAG = '[DEMO]';

    public function run(): void
    {
        $this->command->info('📄 AccreditationReportSeeder — Saltando creación de informes de demostración.');

        // ── Limpiar datos previos ────────────────────────────────────────────
        $this->cleanPreviousData();

        $this->command->info('   ✅ 0 informe(s) de acreditación creado(s). Los informes deben subirse manualmente.');
    }

    private function cleanPreviousData(): void
    {
        DB::table('INFORME_ARCHIVO')
            ->where('observaciones', 'LIKE', self::TAG . '%')
            ->delete();
    }
}
