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
        Schema::table('COMPROMISO_MEJORA_EVIDENCIA_ASIGNACION', function (Blueprint $table) {
            $table->text('comentario')->nullable()->after('evidencia_asignacion_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('COMPROMISO_MEJORA_EVIDENCIA_ASIGNACION', function (Blueprint $table) {
            $table->dropColumn('comentario');
        });
    }
};
