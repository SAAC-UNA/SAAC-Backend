<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Agrega los tipos de acción 'publicar' y 'despublicar' al catálogo TIPO_ACCION.
 * Requeridos por HU-027 para registrar publicación/despublicación de informes de acreditación.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['publicar', 'despublicar'] as $descripcion) {
            $exists = DB::table('TIPO_ACCION')
                ->where('descripcion', $descripcion)
                ->exists();

            if (!$exists) {
                DB::table('TIPO_ACCION')->insert(['descripcion' => $descripcion]);
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
