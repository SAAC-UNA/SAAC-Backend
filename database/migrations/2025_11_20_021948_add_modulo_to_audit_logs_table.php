<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('BITACORA', function (Blueprint $table) {
            $table->string('modulo', 100)->nullable()->after('tipo_accion_id')
                  ->comment('Módulo del sistema donde ocurrió la acción (ej: Usuarios, Evidencias)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('BITACORA', function (Blueprint $table) {
            $table->dropColumn('modulo');
        });
    }
};
