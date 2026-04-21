<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Agrega la columna esta_acreditada a INFORME_ACREDITACION.
     *
     * HU-027 (adición): Indica si la resolución SINAES acredita o no la carrera.
     * Una resolución puede publicarse indicando que la carrera NO fue acreditada
     * (resolución denegatoria), lo cual es información pública relevante.
     */
    public function up(): void
    {
        Schema::table('INFORME_ACREDITACION', function (Blueprint $table) {
            $table->boolean('esta_acreditada')
                  ->default(true)
                  ->after('observaciones')
                  ->comment('true = carrera acreditada, false = resolución denegatoria de SINAES');
        });
    }

    public function down(): void
    {
        Schema::table('INFORME_ACREDITACION', function (Blueprint $table) {
            $table->dropColumn('esta_acreditada');
        });
    }
};
