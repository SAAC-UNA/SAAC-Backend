<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $existing = DB::table('TIPO_ACCION')
            ->whereIn('descripcion', ['publicar', 'despublicar'])
            ->pluck('descripcion')
            ->all();

        foreach (['publicar', 'despublicar'] as $accion) {
            if (!in_array($accion, $existing)) {
                DB::table('TIPO_ACCION')->insert([
                    'descripcion' => $accion,
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        DB::table('TIPO_ACCION')
            ->whereIn('descripcion', ['publicar', 'despublicar'])
            ->delete();
    }
};
