<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Models\AccreditationReport;

/**
 * AccreditationReportSeeder — Datos de demostración para HU-027.
 *
 * Crea informes de acreditación históricos para los ciclos completados
 * (Ciclo 2021-2025) generados por AccreditationCycleSeeder.
 *
 * Prerequisitos:
 *   - AccreditationCycleSeeder  → CICLO_ACREDITACION con estado 'completado'
 *   - UserSeeder                → USUARIO con rol admin
 *
 * IDEMPOTENTE: elimina los informes previos marcados con [DEMO] antes de insertar.
 */
class AccreditationReportSeeder extends Seeder
{
    private const TAG = '[DEMO]';

    public function run(): void
    {
        $this->command->info('📄 AccreditationReportSeeder — Cargando informes de acreditación...');

        // ── Prerequisitos ────────────────────────────────────────────────────
        $admin = DB::table('USUARIO')
            ->where('status', 'active')
            ->orderBy('usuario_id')
            ->first();

        if (!$admin) {
            $this->command->warn('⚠️  No se encontró ningún usuario activo. Saltando seeder.');
            return;
        }

        $ciclosCompletados = DB::table('CICLO_ACREDITACION')
            ->where('estado', 'completado')
            ->get();

        if ($ciclosCompletados->isEmpty()) {
            $this->command->warn('⚠️  No hay ciclos completados. Saltando seeder.');
            $this->command->warn('   Ejecutá primero: php artisan db:seed --class=AccreditationCycleSeeder');
            return;
        }

        // ── Limpiar datos previos ────────────────────────────────────────────
        $this->cleanPreviousData();

        // ── Datos de ejemplo por ciclo ───────────────────────────────────────
        $resoluciones = [
            [
                'numero_resolucion' => 'R-004-2021',
                'vigencia_desde'    => '2021-01-01',
                'vigencia_hasta'    => '2025-12-31',
                'esta_acreditada'   => true,
                'observaciones'     => self::TAG . ' Acreditación otorgada por resolución SINAES 2021.',
            ],
        ];

        $disk = config('saac.storage_disk', 'simulated_nas');
        $insertados = 0;

        foreach ($ciclosCompletados as $index => $ciclo) {
            // Solo usamos la primera resolución de ejemplo (ciclos históricos → una resolución cada uno)
            $resData = $resoluciones[$index % count($resoluciones)];

            // Crear un archivo PDF simulado en disco
            $uuid      = (string) Str::uuid();
            $filename  = "{$uuid}.pdf";
            $pdfContent = "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\n";

            try {
                Storage::disk($disk)->put($filename, $pdfContent);
            } catch (\Throwable $e) {
                // Si el disco no está disponible, guardar path simulado
                $filename = "seeder/{$filename}";
            }

            // Crear registro en ARCHIVO
            $archivoId = DB::table('ARCHIVO')->insertGetId([
                'evidencia_id'    => null,
                'elemento_id'     => null,
                'proceso_id'      => null,
                'usuario_id'      => $admin->usuario_id,
                'fecha_subida'    => now(),
                'tipo'            => 'archivo',
                'path'            => $filename,
                'url'             => null,
                'nombre_original' => "resolucion_sinaes_{$resData['numero_resolucion']}.pdf",
                'tamanio'         => rand(800000, 2000000),
                'tipo_mime'       => 'application/pdf',
                'is_publico'      => true,
                'token_publico'   => (string) Str::uuid(),
                'link_expira_en'  => now()->addYears(10),
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);

            // Crear el informe de acreditación
            DB::table('INFORME_ACREDITACION')->insert([
                'ciclo_acreditacion_id'  => $ciclo->ciclo_acreditacion_id,
                'archivo_id'             => $archivoId,
                'usuario_publicacion_id' => $admin->usuario_id,
                'estado'                 => AccreditationReport::STATUS_PUBLISHED,
                'numero_resolucion'      => $resData['numero_resolucion'] . '-' . $ciclo->ciclo_acreditacion_id,
                'fecha_resolucion'       => now()->toDateString(),
                'vigencia_desde'         => $resData['vigencia_desde'],
                'vigencia_hasta'         => $resData['vigencia_hasta'],
                'fecha_publicacion'      => now(),
                'observaciones'          => $resData['observaciones'],
                'esta_acreditada'        => $resData['esta_acreditada'],
                'created_at'             => now(),
                'updated_at'             => now(),
            ]);

            $insertados++;
        }

        $this->command->info("   ✅ {$insertados} informe(s) de acreditación creado(s).");
    }

    private function cleanPreviousData(): void
    {
        // Eliminar informes DEMO anteriores (por la observación con el TAG)
        $informesDemo = DB::table('INFORME_ACREDITACION')
            ->where('observaciones', 'like', self::TAG . '%')
            ->get();

        foreach ($informesDemo as $informe) {
            // Eliminar el archivo ARCHIVO asociado
            DB::table('ARCHIVO')->where('archivo_id', $informe->archivo_id)->delete();
        }

        DB::table('INFORME_ACREDITACION')
            ->where('observaciones', 'like', self::TAG . '%')
            ->delete();
    }
}
