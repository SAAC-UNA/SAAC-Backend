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
        Schema::table('INFORME_ARCHIVO', function (Blueprint $table) {
            // Drop old enum type and add new string type
            $table->string('tipo', 255)->default('Informes Universitarios')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('INFORME_ARCHIVO', function (Blueprint $table) {
            $table->enum('tipo', ['archivo', 'enlace'])->default('archivo')->change();
        });
    }
};
