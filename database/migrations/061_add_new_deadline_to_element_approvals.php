<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('APROBACION_ELEMENTO', function (Blueprint $table) {
            $table->date('nueva_fecha_limite')->nullable()->after('comentario');
        });
    }

    public function down(): void
    {
        Schema::table('APROBACION_ELEMENTO', function (Blueprint $table) {
            $table->dropColumn('nueva_fecha_limite');
        });
    }
};
