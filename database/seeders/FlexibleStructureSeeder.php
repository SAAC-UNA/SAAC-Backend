<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FlexibleStructureSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🌱 Iniciando FlexibleStructureSeeder (SINAES 2026)...');

        $jsonPath = __DIR__ . '/data/modelo2025_pautas_full.json';
        if (!file_exists($jsonPath)) {
            $this->command->error('❌ No se encontró modelo2025_pautas_full.json en Downloads');
            return;
        }

        $data = json_decode(file_get_contents($jsonPath), true);
        if (!$data) {
            $this->command->error('❌ Error al parsear JSON');
            return;
        }

        DB::table('MODELO_ESTRUCTURA')->insertOrIgnore([
            'nombre' => 'SINAES 2026 - Estructura Flexible',
            'tipo' => 'elemento_flexible',
            'descripcion' => 'Modelo flexible SINAES 2026: Dimensión > Pauta > Fuente.',
            'version' => '2026',
            'activo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $modelo = DB::table('MODELO_ESTRUCTURA')
            ->where('nombre', 'SINAES 2026 - Estructura Flexible')
            ->first();

        $mid = $modelo->modelo_estructura_id;

        if (DB::table('ELEMENTO')->where('modelo_estructura_id', $mid)->exists()) {
            $this->command->warn('  ℹ  ELEMENTO ya tiene datos para este modelo — omitiendo inserción.');
            return;
        }

        $insert = function (array $d) use ($mid): int {
            return DB::table('ELEMENTO')->insertGetId(array_merge([
                'modelo_estructura_id' => $mid,
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ], $d));
        };

        $dimensionIds = [];
        if (isset($data['dimensions'])) {
            foreach ($data['dimensions'] as $d) {
                $dimensionIds[$d['code']] = $insert([
                    'padre_id' => null,
                    'tipo' => 'dimension',
                    'nombre' => $d['name'],
                    'nomenclatura' => $d['code'],
                    'descripcion' => $d['description'],
                ]);
            }
            $this->command->info('  ✓ ' . count($data['dimensions']) . ' Dimensiones');
        }

        $pautaIds = [];
        if (isset($data['pautas'])) {
            foreach ($data['pautas'] as $p) {
                $dimensionCode = 'D' . $p['dimension'];
                $dimensionId = $dimensionIds[$dimensionCode] ?? null;
                $pautaIds[$p['num']] = $insert([
                    'padre_id' => $dimensionId,
                    'tipo' => 'pauta',
                    'categoria' => $p['category'],
                    'nombre' => 'Pauta ' . $p['num'],
                    'nomenclatura' => 'P' . $p['num'],
                    'descripcion' => $p['description'],
                ]);
            }
            $this->command->info('  ✓ ' . count($data['pautas']) . ' Pautas');
        }

        if (isset($data['fuentes'])) {
            foreach ($data['fuentes'] as $f) {
                $pautaNum = $f['pauta'];
                $pautaId = $pautaIds[$pautaNum] ?? null;
                $insert([
                    'padre_id' => $pautaId,
                    'tipo' => 'fuente',
                    'nombre' => 'Fuente ' . $f['num'],
                    'nomenclatura' => 'F' . $f['num'],
                    'descripcion' => $f['description'],
                ]);
            }
            $this->command->info('  ✓ ' . count($data['fuentes']) . ' Fuentes');
        }

        $total = DB::table('ELEMENTO')->where('modelo_estructura_id', $mid)->count();
        $this->command->info("✅ FlexibleStructureSeeder completado — {$total} elementos creados.");
    }
}